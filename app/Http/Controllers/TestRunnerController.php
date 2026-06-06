<?php

namespace App\Http\Controllers;

use App\Actions\Offerings\ResolveOfferingOutcomeAction;
use App\Models\Bid;
use App\Models\Offering;
use App\Models\Property;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DefenderOrgStewardSeeder;
use Database\Seeders\SimulationUsersSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TestRunnerController extends Controller
{
    private const CACHE_PREFIX = 'test_runner.purchase_simulation.';

    public function index()
    {
        return view('test-runner.index', [
            'properties' => Property::orderBy('name')->get(),
            'defaultProperty' => Property::where('status', 1)->orderBy('id')->first() ?: Property::orderBy('id')->first(),
        ]);
    }

    public function startPurchaseSimulation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'property_id' => ['nullable', 'integer', 'exists:properties,id'],
        ]);

        Artisan::call('db:seed', ['--class' => SimulationUsersSeeder::class, '--force' => true]);
        Artisan::call('db:seed', ['--class' => DefenderOrgStewardSeeder::class, '--force' => true]);
        DB::table('properties_status')->updateOrInsert(
            ['id' => '3'],
            ['status' => 'Funded', 'description' => 'Funding target reached and property funding records have been resolved.']
        );

        $property = $this->resolveProperty($data['property_id'] ?? null);
        if (! $property) {
            return response()->json(['message' => 'No property found. Create a property first.'], 422);
        }

        $reset = $this->resetPropertyFunding($property);

        $users = User::where('email', '!=', config('defender.org_steward.email'))
            ->where('role', '!=', 'org_steward')
            ->orderBy('id')
            ->get();

        if ($users->isEmpty()) {
            return response()->json(['message' => 'No users found in the database.'], 422);
        }

        $localUsers = $this->prepareSimulationUsers($property, $users);

        $runId = (string) Str::uuid();
        $offering = $this->resolveOrCreateOffering($property);

        $state = [
            'id' => $runId,
            'status' => 'running',
            'property_id' => $property->id,
            'property_name' => $property->name,
            'property_price' => (float) $property->price,
            'offering_id' => $offering?->id,
            'user_ids' => $users->pluck('id')->values()->all(),
            'next_index' => 0,
            'duration_seconds' => 60,
            'started_at' => Carbon::now()->subMinute()->toIso8601String(),
            'completed_at' => null,
            'resolved' => false,
            'logs' => [
                sprintf('Started 1-minute purchase simulation for property #%d (%s).', $property->id, $property->name),
                sprintf('Reset property funding: removed %d existing bid(s) and restored EUR %s to users.', $reset['bids_removed'], number_format($reset['balance_restored'], 2)),
                sprintf('Loaded %d users from the users table.', $users->count()),
                sprintf('Prepared %d local user(s) for the offering catchment.', $localUsers),
            ],
        ];

        $state = $this->advance($state);
        $this->putState($runId, $state);

        return response()->json($this->payload($state));
    }

    public function purchaseSimulationStatus(string $runId): JsonResponse
    {
        $state = $this->state($runId);
        if (! $state) {
            return response()->json(['message' => 'Simulation run not found.'], 404);
        }

        try {
            $state = $this->advance($state);
        } catch (\Throwable $e) {
            $state['status'] = 'failed';
            $state['logs'][] = 'Simulation stopped: ' . $e->getMessage();
        }

        $this->putState($runId, $state);

        return response()->json($this->payload($state));
    }

    private function advance(array $state): array
    {
        if ($state['status'] !== 'running') {
            return $state;
        }

        $property = Property::find($state['property_id']);
        if (! $property) {
            $state['status'] = 'failed';
            $state['logs'][] = 'Property disappeared while the simulation was running.';
            return $state;
        }

        $startedAt = Carbon::parse($state['started_at']);
        $elapsed = min((int) $startedAt->diffInSeconds(Carbon::now()), (int) $state['duration_seconds']);
        $targetIndex = min(
            count($state['user_ids']),
            (int) floor(($elapsed / max(1, $state['duration_seconds'])) * count($state['user_ids']))
        );

        if ($elapsed >= $state['duration_seconds']) {
            $targetIndex = count($state['user_ids']);
        }

        $offering = $state['offering_id'] ? Offering::find($state['offering_id']) : null;

        while ($state['next_index'] < $targetIndex) {
            $userId = $state['user_ids'][$state['next_index']];
            $user = User::find($userId);
            $state['next_index']++;

            if (! $user) {
                continue;
            }

            $remaining = max(0, (float) $property->price - (float) $property->bids()->sum('amount'));
            if ($remaining <= 0) {
                $state['logs'][] = 'Property is fully funded. Ending simulation early.';
                return $this->complete($state);
            }

            $remainingUsers = max(1, count($state['user_ids']) - ($state['next_index'] - 1));
            $amount = $this->purchaseAmount($user, $remaining, $remainingUsers);
            if ($amount <= 0) {
                $state['logs'][] = sprintf('%s skipped: insufficient available balance for the remaining amount.', $user->username);
                continue;
            }

            $bidTime = $startedAt->copy()->addSeconds((int) round(($state['duration_seconds'] / max(1, count($state['user_ids']))) * ($state['next_index'] - 1)));

            if ($this->placeBid($property, $user, $amount, $offering, $bidTime)) {
                $state['logs'][] = sprintf(
                    '[%03d/%03d] %s bought EUR %s.',
                    $state['next_index'],
                    count($state['user_ids']),
                    $user->username,
                    number_format($amount, 2)
                );
            }
        }

        if ($state['next_index'] >= count($state['user_ids']) || $elapsed >= $state['duration_seconds']) {
            return $this->complete($state);
        }

        return $state;
    }

    private function complete(array $state): array
    {
        if (($state['resolved'] ?? false) === false) {
            $resolution = $this->resolveFundedProperty((int) $state['property_id'], $state['offering_id'] ?? null);
            if ($resolution['offering_id'] ?? null) {
                $state['offering_id'] = $resolution['offering_id'];
            }
            $state['resolved'] = true;
            $state['logs'] = array_merge($state['logs'], $resolution['logs']);
        }

        $state['status'] = 'completed';
        $state['completed_at'] = Carbon::now()->toIso8601String();
        $state['logs'][] = 'Simulation completed. Ownership and settlement tables are ready.';

        return $state;
    }

    private function payload(array $state): array
    {
        $property = Property::find($state['property_id']);
        $funded = $property ? (float) $property->bids()->sum('amount') : 0.0;
        $price = $property ? (float) $property->price : (float) ($state['property_price'] ?? 0);
        $startedAt = Carbon::parse($state['started_at']);
        $elapsed = $state['status'] === 'running'
            ? min($startedAt->diffInSeconds(Carbon::now()), (int) $state['duration_seconds'])
            : (int) $state['duration_seconds'];

        return [
            'id' => $state['id'],
            'status' => $state['status'],
            'property' => [
                'id' => $state['property_id'],
                'name' => $state['property_name'],
                'price' => $price,
                'funded' => $funded,
                'funded_pct' => $price > 0 ? round(($funded / $price) * 100, 2) : 0,
            ],
            'progress' => [
                'elapsed_seconds' => $elapsed,
                'duration_seconds' => (int) $state['duration_seconds'],
                'users_processed' => (int) $state['next_index'],
                'users_total' => count($state['user_ids']),
                'pct' => round(($elapsed / max(1, (int) $state['duration_seconds'])) * 100, 2),
            ],
            'logs' => array_slice($state['logs'], -120),
            'ownership' => $this->ownershipRows($state['property_id'], $state['offering_id'] ?? null),
            'settlement' => $this->settlementSummary($state['property_id'], $state['offering_id'] ?? null),
        ];
    }

    private function ownershipRows(int $propertyId, ?int $offeringId = null): array
    {
        $property = Property::find($propertyId);
        if (! $property) {
            return [];
        }

        $offering = $this->offeringForSummary($propertyId, $offeringId);
        $equityByUser = $offering
            ? DB::table('equity_positions')
                ->where('offering_id', $offering->id)
                ->where('position_type', 'LOCAL_INVESTOR')
                ->get()
                ->keyBy('holder_user_id')
            : collect();

        return Bid::query()
            ->where('property_id', $propertyId)
            ->with('user')
            ->orderByDesc('amount')
            ->get()
            ->map(function (Bid $bid) use ($equityByUser) {
                $isLocal = (bool) ($bid->is_local_at_bid_time ?? $bid->is_local);
                $equity = $equityByUser->get($bid->user_id);
                $ownershipPct = $equity ? round(((int) $equity->economic_bp) / 100, 4) : 0.0;

                return [
                    'user_id' => $bid->user_id,
                    'username' => $bid->user?->username ?: 'User #' . $bid->user_id,
                    'email' => $bid->user?->email,
                    'amount' => (float) $bid->amount,
                    'ownership_pct' => $ownershipPct,
                    'position' => $equity ? 'Local equity' : ($isLocal ? 'Pending local equity' : 'Loan note only'),
                    'is_local' => $isLocal,
                    'status' => $bid->status,
                ];
            })
            ->values()
            ->all();
    }

    private function resolveProperty(?int $propertyId): ?Property
    {
        if ($propertyId) {
            return Property::find($propertyId);
        }

        return Property::where('status', 1)->orderBy('id')->first() ?: Property::orderBy('id')->first();
    }

    private function resolveOrCreateOffering(Property $property): ?Offering
    {
        if (! Schema::hasTable('offerings')) {
            return null;
        }

        $existing = Offering::where('property_id', $property->id)
            ->where('status', 'OPEN')
            ->orderBy('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return Offering::create([
            'property_id' => $property->id,
            'name' => $property->name . ' Funding Test',
            'status' => 'OPEN',
            'target_raise' => number_format((float) $property->price, 2, '.', ''),
            'target_raise_cents' => (int) round(((float) $property->price) * 100),
            'hard_cap_cents' => (int) round(((float) $property->price) * 100),
            'org_reserve_pct' => '10.00',
            'min_local_capital_pct' => 25,
            'min_local_investor_count' => 3,
            'require_local_threshold_for_settlement' => true,
                    'campaign_end' => now()->addMinute(),
        ]);
    }

    private function purchaseAmount(User $user, float $remaining, int $remainingUsers): float
    {
        $fairShare = $remaining / max(1, $remainingUsers);
        $variance = $remainingUsers > 1 ? random_int(85, 115) / 100 : 1;
        $target = $remainingUsers > 1 ? max(0.01, $fairShare * $variance) : $remaining;
        $reserveForOthers = $remainingUsers > 1 ? max(0, ($remainingUsers - 1) * 0.01) : 0;
        $available = min((float) $user->balance, $remaining - $reserveForOthers, $target);

        if ($available <= 0) {
            return 0.0;
        }

        return round($available, 2);
    }

    private function resetPropertyFunding(Property $property): array
    {
        return DB::transaction(function () use ($property) {
            $bids = Bid::where('property_id', $property->id)->lockForUpdate()->get();
            $balanceRestored = 0.0;

            foreach ($bids as $bid) {
                $amount = (float) $bid->amount;
                if ($amount > 0 && $bid->user_id) {
                    User::whereKey($bid->user_id)->increment('balance', $amount);
                    User::whereKey($bid->user_id)->increment('funds', $amount);
                    $balanceRestored += $amount;
                }
            }

            $bidIds = $bids->pluck('id')->all();
            if ($bidIds && Schema::hasTable('refund_records')) {
                DB::table('refund_records')->whereIn('bid_id', $bidIds)->delete();
            }

            Bid::where('property_id', $property->id)->delete();

            $offeringIds = Schema::hasTable('offerings')
                ? Offering::where('property_id', $property->id)->pluck('id')->all()
                : [];

            if (Schema::hasTable('investments')) {
                DB::table('investments')->where('property_id', $property->id)->delete();
            }

            if ($offeringIds) {
                foreach (['equity_positions', 'loan_notes', 'funding_events', 'decidim_governance_syncs'] as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->whereIn('offering_id', $offeringIds)->delete();
                    }
                }

                Offering::whereIn('id', $offeringIds)->update([
                    'status' => 'OPEN',
                    'failure_reason' => null,
                    'failure_notes' => null,
                    'resolved_at' => null,
                    'campaign_end' => now()->addMinute(),
                ]);
            }

            $property->forceFill(['status' => 1])->save();

            return [
                'bids_removed' => $bids->count(),
                'balance_restored' => $balanceRestored,
            ];
        });
    }

    private function prepareSimulationUsers(Property $property, $users): int
    {
        $propertyRegion = $this->eircodeRegion($property->eircode) ?: 'D01';
        if (! $property->eircode) {
            $property->forceFill(['eircode' => $propertyRegion . 'TEST'])->save();
        }

        $localTarget = max(3, (int) ceil($users->count() * 0.35));
        $propertyPrice = (float) $property->price;
        $minimumBalance = round(($propertyPrice / max(1, $users->count())) * 1.25, 2);
        $preparedLocals = 0;

        foreach ($users->values() as $index => $user) {
            $updates = [];
            if ((float) $user->balance < $minimumBalance) {
                $updates['balance'] = $minimumBalance;
                $updates['funds'] = max((float) ($user->funds ?? 0), $minimumBalance);
            }

            if ($index < $localTarget) {
                $updates['eircode_region'] = $propertyRegion;
                $preparedLocals++;
            }

            if ($updates !== []) {
                $user->forceFill($updates)->save();
            }
        }

        return $preparedLocals;
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

            if ($amount <= 0 || ! $lockedUser || (float) $lockedUser->balance < $amount) {
                return false;
            }

            $isLocal = $this->isLocal($property, $lockedUser);
            $propertyRegion = $this->eircodeRegion($property->eircode);
            $userRegion = $this->eircodeRegion($lockedUser->eircode_region);
            $bidData = [
                'amount' => $amount,
                'status' => $offering ? 'PLEDGED' : 'pending',
                'updated_at' => $bidTime,
            ];

            $this->addBidColumnIfPresent($bidData, 'original_amount_cents', (int) round($amount * 100));
            $this->addBidColumnIfPresent($bidData, 'is_local', $isLocal);
            $this->addBidColumnIfPresent($bidData, 'is_local_at_bid_time', $isLocal);
            $this->addBidColumnIfPresent($bidData, 'local_eligibility_reason', [
                'type' => 'simulation_eircode_region',
                'property_region' => $propertyRegion,
                'user_region' => $userRegion,
            ]);
            $this->addBidColumnIfPresent($bidData, 'local_area_version', $propertyRegion);

            if (Schema::hasColumn('bids', 'offering_id')) {
                $bidData['offering_id'] = $offering?->id;
            }

            if (Schema::hasColumn('bids', 'bid_time')) {
                $bidData['bid_time'] = $bidTime;
            }

            if ($existingBid) {
                $difference = $amount - (float) $existingBid->amount;
                if ($difference <= 0 || (float) $lockedUser->balance < $difference) {
                    return false;
                }

                $lockedUser->balance -= $difference;
                $lockedUser->funds = max(0, (float) $lockedUser->funds - $difference);
                $lockedUser->save();
                $existingBid->forceFill($bidData)->save();

                return true;
            }

            $lockedUser->balance -= $amount;
            $lockedUser->funds = max(0, (float) $lockedUser->funds - $amount);
            $lockedUser->save();

            $bid = new Bid($bidData + [
                'property_id' => $property->id,
                'user_id' => $lockedUser->id,
            ]);
            $bid->created_at = $bidTime;
            $bid->updated_at = $bidTime;
            $bid->save();

            return true;
        });
    }

    private function isLocal(Property $property, User $user): bool
    {
        $propertyRegion = $this->eircodeRegion($property->eircode);
        $userRegion = $this->eircodeRegion($user->eircode_region);

        return $propertyRegion && $userRegion && $propertyRegion === $userRegion;
    }

    private function eircodeRegion(?string $eircode): ?string
    {
        return $eircode ? strtoupper(substr(preg_replace('/\s+/', '', $eircode), 0, 3)) : null;
    }

    private function addBidColumnIfPresent(array &$bidData, string $column, mixed $value): void
    {
        if (Schema::hasColumn('bids', $column)) {
            $bidData[$column] = $value;
        }
    }

    private function state(string $runId): ?array
    {
        return Cache::get(self::CACHE_PREFIX . $runId);
    }

    private function putState(string $runId, array $state): void
    {
        Cache::put(self::CACHE_PREFIX . $runId, $state, now()->addHours(2));
    }

    private function resolveFundedProperty(int $propertyId, ?int $offeringId = null): array
    {
        $property = Property::find($propertyId);
        if (! $property) {
            return ['logs' => ['Property could not be resolved because it no longer exists.']];
        }

        $funded = (float) $property->bids()->sum('amount');
        $target = (float) $property->price;
        if ($funded + 0.01 < $target) {
            return ['logs' => [sprintf('Property is not fully funded yet: EUR %s / EUR %s.', number_format($funded, 2), number_format($target, 2))]];
        }

        $offering = $offeringId ? Offering::where('property_id', $property->id)->find($offeringId) : null;
        $offering ??= $this->resolveOrCreateOffering($property);
        if (! $offering) {
            $property->forceFill(['status' => 3])->save();
            return [
                'offering_id' => null,
                'logs' => ['Property marked as funded. No offerings table is available for settlement records.'],
            ];
        }

        $targetCents = (int) round($target * 100);
        $offering->forceFill([
            'status' => 'CLOSED',
            'target_raise' => number_format($target, 2, '.', ''),
            'target_raise_cents' => $targetCents,
            'hard_cap_cents' => $targetCents,
            'campaign_end' => now()->subSecond(),
        ])->save();

        Bid::where('property_id', $property->id)->update([
            'offering_id' => $offering->id,
            'status' => 'PLEDGED',
        ]);

        $resolved = (new ResolveOfferingOutcomeAction())->handle($offering->fresh(), null, $this->orgStewardUserId());

        if ($resolved->status === 'SETTLED') {
            $property->forceFill(['status' => 3])->save();
        }

        $totals = $this->settlementTotals($resolved);

        return [
            'offering_id' => $resolved->id,
            'logs' => [
                sprintf('Offering #%d resolved with status %s.', $resolved->id, $resolved->status),
                sprintf('Final equity total: %s%%. Local investor equity: %s%%. Treasury: %s%%.', $totals['total_equity_economic_pct'], $totals['local_investor_economic_pct'], $totals['treasury_economic_pct']),
                sprintf('Property status updated to %s.', $resolved->status === 'SETTLED' ? 'Funded' : 'unchanged because settlement did not complete'),
            ],
        ];
    }

    private function settlementSummary(int $propertyId, ?int $offeringId = null): array
    {
        $property = Property::find($propertyId);
        $offering = $this->offeringForSummary($propertyId, $offeringId);

        if (! $property) {
            return [];
        }

        return [
            'property_status' => (string) $property->status,
            'property_status_label' => DB::table('properties_status')->where('id', (string) $property->status)->value('status') ?: (string) $property->status,
            'offering_id' => $offering?->id,
            'offering_status' => $offering?->status,
            'failure_reason' => $offering?->failure_reason,
            'org_steward' => User::where('email', config('defender.org_steward.email'))->value('username'),
            'equity_positions' => $offering ? $offering->equityPositions()->with('holder')->get()->map(fn ($position) => [
                'holder' => $position->holder?->username ?: $position->holder_type,
                'position_type' => $position->position_type,
                'economic_bp' => (int) $position->economic_bp,
                'voting_bp' => (int) $position->voting_bp,
                'can_vote' => (bool) $position->can_vote,
            ])->values()->all() : [],
            'loan_notes' => $offering ? $offering->loanNotes()->with('lender')->get()->map(fn ($note) => [
                'lender' => $note->lender?->username ?: 'User #' . $note->lender_user_id,
                'principal_cents' => (int) ($note->principal_cents ?: round(((float) $note->principal) * 100)),
                'voting_scope' => $note->voting_scope,
                'status' => $note->status,
            ])->values()->all() : [],
            'refund_count' => $offering && Schema::hasTable('refund_records') ? $offering->refundRecords()->count() : 0,
            'funding_event_count' => $offering && Schema::hasTable('funding_events') ? DB::table('funding_events')->where('offering_id', $offering->id)->count() : 0,
            'totals' => $this->settlementTotals($offering),
        ];
    }

    private function settlementTotals(?Offering $offering): array
    {
        if (! $offering) {
            return [
                'local_investor_economic_pct' => 0,
                'org_reserve_economic_pct' => 0,
                'treasury_economic_pct' => 0,
                'total_equity_economic_pct' => 0,
                'total_voting_pct' => 0,
                'loan_principal_cents' => 0,
            ];
        }

        $positions = $offering->equityPositions()->get();
        $loanPrincipal = $offering->loanNotes()->get()->sum(fn ($note) => (int) ($note->principal_cents ?: round(((float) $note->principal) * 100)));

        return [
            'local_investor_economic_pct' => round($positions->where('position_type', 'LOCAL_INVESTOR')->sum('economic_bp') / 100, 4),
            'org_reserve_economic_pct' => round($positions->where('position_type', 'ORG_RESERVE')->sum('economic_bp') / 100, 4),
            'treasury_economic_pct' => round($positions->where('position_type', 'TREASURY')->sum('economic_bp') / 100, 4),
            'total_equity_economic_pct' => round($positions->sum('economic_bp') / 100, 4),
            'total_voting_pct' => round($positions->sum('voting_bp') / 100, 4),
            'loan_principal_cents' => $loanPrincipal,
        ];
    }

    private function offeringForSummary(int $propertyId, ?int $offeringId = null): ?Offering
    {
        if (! Schema::hasTable('offerings')) {
            return null;
        }

        if ($offeringId) {
            $offering = Offering::where('property_id', $propertyId)->find($offeringId);
            if ($offering) {
                return $offering;
            }
        }

        return Offering::where('property_id', $propertyId)->latest('id')->first();
    }

    private function orgStewardUserId(): ?int
    {
        return User::where('email', config('defender.org_steward.email'))->value('id');
    }
}
