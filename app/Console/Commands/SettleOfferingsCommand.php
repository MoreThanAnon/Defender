<?php

namespace App\Console\Commands;

use App\Actions\Offerings\ResolveOfferingOutcomeAction;
use App\Models\EquityClass;
use App\Models\Offering;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SettleOfferingsCommand extends Command
{
    protected $signature = 'offerings:settle';
    protected $description = 'Settle all closed offerings where the campaign has ended and the target was reached';

    public function handle()
    {
        $now = Carbon::now();

        $offerings = Offering::where('status', 'CLOSED')
            ->whereNotNull('campaign_end')
            ->where('campaign_end', '<=', $now)
            ->get();

        if ($offerings->isEmpty()) {
            $this->info('No offerings to settle.');

            return 0;
        }

        $resolver = new ResolveOfferingOutcomeAction();

        foreach ($offerings as $offering) {
            $this->info("Settling offering: {$offering->id} - {$offering->name}");

            $equityClass = EquityClass::firstOrCreate(
                ['property_id' => $offering->property_id, 'code' => 'ORD-A'],
                array_filter([
                    'name' => 'Ordinary A',
                    'hotel_id' => Schema::hasColumn('equity_classes', 'hotel_id') ? $offering->property_id : null,
                ], fn ($value) => $value !== null)
            );

            try {
                $resolved = $resolver->handle($offering, $equityClass->id, $this->orgStewardUserId());
                $this->info("Offering {$offering->id} resolved as {$resolved->status}.");
            } catch (\Throwable $e) {
                $this->error("Failed to settle offering {$offering->id}: " . $e->getMessage());
            }
        }

        return 0;
    }

    private function orgStewardUserId(): ?int
    {
        return User::where('email', config('defender.org_steward.email'))->value('id');
    }
}
