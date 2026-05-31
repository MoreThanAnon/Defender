<?php

namespace App\Actions\Offerings;

use App\Models\Bid;
use App\Models\EquityPosition;
use App\Models\FundingEvent;
use App\Models\LoanNote;
use App\Models\Offering;
use App\Services\EquityAllocationService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SettleOffering
{
    public function __construct(private EquityAllocationService $svc) {}

    public function handle(Offering $offering, int $equityClassId, int $orgUserId = null): void
    {
        if ($offering->status !== 'CLOSED') {
            throw new RuntimeException('Offering must be CLOSED before settlement.');
        }

        $bidsCollection = Bid::where('offering_id', $offering->id)
            ->whereIn('status', ['PLEDGED', 'ACCEPTED'])
            ->get();

        $bids = $bidsCollection
            ->map(function ($bid) {
                return [
                    'investor_id' => (int) $bid->user_id,
                    'amount' => number_format((float) $bid->amount, 2, '.', ''),
                    'is_local' => (bool) $bid->is_local,
                ];
            })
            ->values()
            ->all();

        $result = $this->svc->allocate(
            bids: $bids,
            orgReservePct: number_format((float) $offering->org_reserve_pct, 2, '.', ''),
            equityPrecisionPct: '0.01'
        );

        DB::transaction(function () use ($offering, $equityClassId, $result, $orgUserId) {
            EquityPosition::create([
                'offering_id' => $offering->id,
                'equity_class_id' => $equityClassId,
                'holder_user_id' => $orgUserId,
                'holder_type' => 'ORG',
                'pct_ownership' => $result['org_equity_pct'],
                'source' => 'ORG_RESERVE',
                'locked_until' => null,
            ]);

            FundingEvent::create([
                'offering_id' => $offering->id,
                'event_type' => 'equity.org_reserve',
                'equity_pct' => $result['org_equity_pct'],
                'metadata' => [
                    'source' => 'ORG_RESERVE',
                ],
            ]);

            foreach ($result['investor_equity'] as $userId => $pct) {
                EquityPosition::create([
                    'offering_id' => $offering->id,
                    'equity_class_id' => $equityClassId,
                    'holder_user_id' => $userId,
                    'holder_type' => 'USER',
                    'pct_ownership' => $pct,
                    'source' => 'INVESTOR_POOL',
                ]);

                FundingEvent::create([
                    'offering_id' => $offering->id,
                    'user_id' => $userId,
                    'event_type' => 'equity.allocated',
                    'equity_pct' => $pct,
                    'metadata' => [
                        'source' => 'INVESTOR_POOL',
                    ],
                ]);
            }

            if ((float) $result['unallocated_equity_pct'] > 0) {
                EquityPosition::create([
                    'offering_id' => $offering->id,
                    'equity_class_id' => $equityClassId,
                    'holder_user_id' => null,
                    'holder_type' => 'TREASURY',
                    'pct_ownership' => $result['unallocated_equity_pct'],
                    'source' => 'INVESTOR_POOL',
                ]);

                FundingEvent::create([
                    'offering_id' => $offering->id,
                    'event_type' => 'equity.treasury',
                    'equity_pct' => $result['unallocated_equity_pct'],
                    'metadata' => [
                        'source' => 'INVESTOR_POOL',
                    ],
                ]);
            }

            foreach ($result['loan_notes'] as $userId => $principal) {
                LoanNote::create([
                    'offering_id' => $offering->id,
                    'lender_user_id' => $userId,
                    'principal' => $principal,
                    'interest_rate_bp' => 600,
                    'term_months' => 36,
                    'status' => 'ACTIVE',
                ]);

                FundingEvent::create([
                    'offering_id' => $offering->id,
                    'user_id' => $userId,
                    'event_type' => 'loan_note.created',
                    'amount' => $principal,
                    'metadata' => [
                        'interest_rate_bp' => 600,
                        'term_months' => 36,
                    ],
                ]);
            }

            FundingEvent::create([
                'offering_id' => $offering->id,
                'event_type' => 'offering.settled',
                'metadata' => [
                    'org_equity_pct' => $result['org_equity_pct'],
                    'investor_equity' => $result['investor_equity'],
                    'unallocated_equity_pct' => $result['unallocated_equity_pct'],
                    'loan_notes' => $result['loan_notes'],
                ],
            ]);

            Bid::where('offering_id', $offering->id)->update(['status' => 'ACCEPTED']);
            $offering->update(['status' => 'SETTLED']);
        });
    }
}
