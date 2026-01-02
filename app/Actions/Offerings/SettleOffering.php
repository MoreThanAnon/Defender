<?php

namespace App\Actions\Offerings;

use App\Models\{Offering,Bid,EquityClass,EquityPosition,LoanNote};
use App\Services\EquityAllocationService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Atomic "settlement" step:
 * - Read bids for a CLOSED offering,
 * - Run allocation (org % + local % + loans for non-locals),
 * - Write cap table rows,
 * - Write loan note rows,
 * - Flip offering status to SETTLED.
 *
 * If *any* step fails, the whole DB transaction rolls back.
 */
class SettleOffering
{
    public function __construct(private EquityAllocationService $svc) {}

    /**
     * @param Offering $offering        The offering to settle (must be CLOSED)
     * @param string      $equityClass   Which equity class we’re issuing (e.g., ORD-A):https://www.investopedia.com/terms/c/class.asp
     * @param int|null $orgUserId       If you want the org’s 10% tied to a user id (nullable)
     */
    public function handle(Offering $offering, $equityClass, int $orgUserId = null): void
    {
        // Safety: disallow settlement unless explicitly CLOSED.
        if ($offering->status !== 'CLOSED') {
            throw new RuntimeException('Offering must be CLOSED before settlement.');
        }

        // Gather the pledges we’re going to allocate.
        // Status filter: include confirmed money (PLEDGED/ACCEPTED).
        $bidsQuery = Bid::where('offering_id', $offering->id)
            ->whereIn('status', ['PLEDGED', 'ACCEPTED']);

        $bidsCollection = $bidsQuery->get();

        $bids = $bidsCollection
            ->map(function ($bid) {
                return [
                    'investor_id' => (int) $bid->user_id,
                    // Normalize amount string for allocator
                    'amount'      => number_format((float) $bid->amount, 2, '.', ''),
                    'is_local'    => (bool) $bid->is_local,
                ];
            })
            ->values()
            ->all();

        // Core math: figure out who gets what % and who gets loan notes.
        $result = $this->svc->allocate(
            bids: $bids,
            orgReservePct: number_format((float)$offering->org_reserve_pct, 2, '.', ''),
            equityPrecisionPct: '0.01' // round to 1 basis point (0.01%)
        );

        // Write everything in one transaction so we never half-settle.
        DB::transaction(function () use ($offering, $equityClass, $result, $orgUserId) {
            // 1) Org’s 10% equity position (your platform’s stake)
            EquityPosition::create([
                'offering_id'     => $offering->id,
                'equity_class' => $equityClass,
                'holder_user_id'  => $orgUserId,   // or leave null + use holder_type to mark org
                'holder_type'     => 'ORG',
                'pct_ownership'   => $result['org_equity_pct'],
                'source'          => 'ORG_RESERVE',
                'locked_until'    => null,         // set a lockup if your terms require
            ]);

            // 2) Local investor equity positions
            foreach ($result['investor_equity'] as $userId => $pct) {
                EquityPosition::create([
                    'offering_id'     => $offering->id,
                    'equity_class' => $equityClass,
                    'holder_user_id'  => $userId,
                    'holder_type'     => 'USER',
                    'pct_ownership'   => $pct,
                    'source'          => 'INVESTOR_POOL',
                ]);
            }

            // 3) Any rounding remainder stays in treasury/unissued
            if ((float)$result['unallocated_equity_pct'] > 0) {
                EquityPosition::create([
                    'offering_id'     => $offering->id,
                    'equity_class' => $equityClass,
                    'holder_user_id'  => null,      // null → treasury
                    'holder_type'     => 'TREASURY',
                    'pct_ownership'   => $result['unallocated_equity_pct'],
                    'source'          => 'INVESTOR_POOL',
                ]);
            }

            // 4) Create loan notes for non-locals (principal only here)
            //    Interest rate / term could be pulled from Offering settings instead of hardcoding.
            foreach ($result['loan_notes'] as $userId => $principal) {
                LoanNote::create([
                    'offering_id'     => $offering->id,
                    'lender_user_id'  => $userId,
                    'principal'       => $principal,
                    'interest_rate_bp'=> 600,   // example: 6.00% annual (600 basis points)
                    'term_months'     => 36,    // example: 3-year note
                    'status'          => 'ACTIVE',
                ]);
            }

            // 5) Mark all pledges as accepted (or refine per your refund rules)
            Bid::where('offering_id', $offering->id)
               ->update(['status' => 'ACCEPTED']);

            // 6) Flip offering state to SETTLED (cap table + loans are now officially live)
            $offering->update(['status' => 'SETTLED']);
        });
    }
}