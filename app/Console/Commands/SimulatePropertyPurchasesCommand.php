<?php

namespace App\Console\Commands;

use App\Models\Bid;
use App\Models\Offering;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\SimulationUsersSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SimulatePropertyPurchasesCommand extends Command
{
    protected $signature = 'simulation:property-purchases
        {property_id? : Property ID to fund. Defaults to the first active property.}
        {--users=100 : Number of simulation users to involve.}
        {--minutes=10 : Number of minutes to spread purchases across.}
        {--min=500 : Minimum purchase amount per user.}
        {--max=5000 : Maximum purchase amount per user.}
        {--offering-id= : Optional offering ID to connect bids to.}
        {--seed-users : Seed/update the 100 funded simulation users first.}
        {--no-wait : Fast-forward timestamps instead of waiting in real time.}';

    protected $description = 'Simulate funded users buying into a property by placing bids over a configured time window.';

    public function handle(): int
    {
        if ($this->option('seed-users')) {
            $this->call('db:seed', ['--class' => SimulationUsersSeeder::class]);
        }

        $property = $this->resolveProperty();

        if (! $property) {
            $this->error('No property found. Seed or create a property first.');

            return self::FAILURE;
        }

        $offering = $this->resolveOffering($property);
        $users = User::where('email', 'like', 'sim-user-%@example.com')
            ->where('balance', '>', 0)
            ->inRandomOrder()
            ->limit((int) $this->option('users'))
            ->get();

        if ($users->isEmpty()) {
            $this->error('No funded simulation users found. Run with --seed-users first.');

            return self::FAILURE;
        }

        $minutes = max(1, (int) $this->option('minutes'));
        $waitSeconds = ($minutes * 60) / max($users->count(), 1);
        $startedAt = Carbon::now();

        $this->info(sprintf(
            'Simulating %d users buying into property #%d (%s) over %d minute(s).',
            $users->count(),
            $property->id,
            $property->name,
            $minutes
        ));

        if ($offering) {
            $this->line("Connecting bids to offering #{$offering->id} ({$offering->name}).");
        }

        $placed = 0;
        $totalPlaced = 0.0;

        foreach ($users as $index => $user) {
            $property->refresh();
            $currentTotal = (float) $property->bids()->sum('amount');
            $remaining = (float) $property->price - $currentTotal;

            if ($remaining <= 0) {
                $this->info('Property is fully funded. Stopping simulation.');
                break;
            }

            $amount = $this->purchaseAmount($user, $remaining);

            if ($amount <= 0) {
                continue;
            }

            $bidTime = $this->option('no-wait')
                ? $startedAt->copy()->addSeconds((int) round($waitSeconds * $index))
                : Carbon::now();

            $createdBid = $this->placeBid($property, $user, $amount, $offering, $bidTime);

            if ($createdBid) {
                $placed++;
                $totalPlaced += $amount;
                $this->line(sprintf(
                    '[%03d/%03d] %s bought EUR %s. Total funded: EUR %s / EUR %s',
                    $index + 1,
                    $users->count(),
                    $user->username,
                    number_format($amount, 2),
                    number_format($property->bids()->sum('amount'), 2),
                    number_format((float) $property->price, 2)
                ));
            }

            if (! $this->option('no-wait') && $index < $users->count() - 1) {
                sleep((int) max(1, floor($waitSeconds)));
            }
        }

        $this->info(sprintf(
            'Done. Placed %d purchase(s), totalling EUR %s.',
            $placed,
            number_format($totalPlaced, 2)
        ));

        return self::SUCCESS;
    }

    private function resolveProperty(): ?Property
    {
        $propertyId = $this->argument('property_id');

        if ($propertyId) {
            return Property::find($propertyId);
        }

        return Property::where('status', 1)->orderBy('id')->first()
            ?: Property::orderBy('id')->first();
    }

    private function resolveOffering(Property $property): ?Offering
    {
        if (! Schema::hasTable('offerings')) {
            return null;
        }

        $offeringId = $this->option('offering-id');

        if ($offeringId) {
            return Offering::where('property_id', $property->id)->find($offeringId);
        }

        return Offering::where('property_id', $property->id)
            ->where('status', 'OPEN')
            ->orderBy('id')
            ->first();
    }

    private function purchaseAmount(User $user, float $remaining): float
    {
        $min = max(1, (float) $this->option('min'));
        $max = max($min, (float) $this->option('max'));
        $available = min((float) $user->balance, $remaining, $max);

        if ($available < $min && $remaining >= $min) {
            return 0.0;
        }

        return round(random_int((int) $min * 100, (int) max($min, $available) * 100) / 100, 2);
    }

    private function placeBid(Property $property, User $user, float $amount, ?Offering $offering, Carbon $bidTime): bool
    {
        return DB::transaction(function () use ($property, $user, $amount, $offering, $bidTime) {
            $lockedUser = User::whereKey($user->id)->lockForUpdate()->first();
            $existingBid = Bid::where('property_id', $property->id)
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            $currentTotal = (float) Bid::where('property_id', $property->id)
                ->when($existingBid, fn ($query) => $query->where('id', '!=', $existingBid->id))
                ->sum('amount');

            $amount = min($amount, (float) $property->price - $currentTotal);

            if ($amount <= 0 || $lockedUser->balance < $amount) {
                return false;
            }

            if ($existingBid) {
                $difference = $amount - (float) $existingBid->amount;

                if ($difference <= 0 || $lockedUser->balance < $difference) {
                    return false;
                }

                $lockedUser->balance -= $difference;
                $lockedUser->save();

                $updates = [
                    'amount' => $amount,
                    'status' => $offering ? 'PLEDGED' : 'pending',
                    'updated_at' => $bidTime,
                ];

                if (Schema::hasColumn('bids', 'offering_id')) {
                    $updates['offering_id'] = $offering?->id;
                }

                if (Schema::hasColumn('bids', 'bid_time')) {
                    $updates['bid_time'] = $bidTime;
                }

                $existingBid->forceFill($updates)->save();

                return true;
            }

            $lockedUser->balance -= $amount;
            $lockedUser->save();

            $bidData = [
                'property_id' => $property->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'status' => $offering ? 'PLEDGED' : 'pending',
            ];

            if (Schema::hasColumn('bids', 'offering_id')) {
                $bidData['offering_id'] = $offering?->id;
            }

            if (Schema::hasColumn('bids', 'is_local')) {
                $bidData['is_local'] = $this->isLocal($property, $user);
            }

            $bid = new Bid($bidData);
            $bid->created_at = $bidTime;
            $bid->updated_at = $bidTime;

            if (Schema::hasColumn('bids', 'bid_time')) {
                $bid->forceFill(['bid_time' => $bidTime]);
            }

            $bid->save();

            return true;
        });
    }

    private function isLocal(Property $property, User $user): bool
    {
        $propertyRegion = $property->eircode
            ? strtoupper(substr(preg_replace('/\s+/', '', $property->eircode), 0, 3))
            : null;
        $userRegion = $user->eircode_region
            ? strtoupper(substr(preg_replace('/\s+/', '', $user->eircode_region), 0, 3))
            : null;

        return $propertyRegion && $userRegion && $propertyRegion === $userRegion;
    }
}
