<?php

namespace App\Actions\Offerings;

use App\Models\EquityPosition;
use App\Models\Offering;
use Illuminate\Support\Facades\Schema;

class AllocateEquityPositionsAction
{
    use RecordsFundingEvents;

    public function __construct(private ApplyInvestorVotingCapAction $votingCap = new ApplyInvestorVotingCapAction()) {}

    public function handle(Offering $offering, int $equityClassId, ?int $orgUserId = null): array
    {
        if ($offering->equityPositions()->exists()) {
            return ['excess_loan_cents' => []];
        }

        $acceptedLocal = $offering->bids()
            ->where('accepted_amount_cents', '>', 0)
            ->get()
            ->filter(fn ($bid) => (bool) ($bid->is_local_at_bid_time ?? $bid->is_local))
            ->groupBy('user_id')
            ->map(fn ($bids) => $bids->sum('accepted_amount_cents'))
            ->all();

        $orgBp = (int) round(((float) $offering->org_reserve_pct) * 100);
        $poolBp = max(0, 10000 - $orgBp);
        $investorBp = $this->largestRemainderBp($acceptedLocal, $poolBp);
        $cap = $this->votingCap->handle($offering, $investorBp, $acceptedLocal);

        $this->createPosition($offering, $equityClassId, $orgUserId, 'ORG', 'ORG_RESERVE', $orgBp, false);
        $this->recordFundingEvent($offering, 'equity.org_reserve', [
            'user_id' => $orgUserId,
            'equity_pct' => $orgBp / 100,
            'metadata' => ['idempotency_key' => 'equity:org_reserve', 'voting_bp' => 0, 'economic_bp' => $orgBp],
        ]);

        foreach ($investorBp as $userId => $rawEconomicBp) {
            $votingBp = (int) ($cap['voting_bp'][$userId] ?? $rawEconomicBp);
            $economicBp = $votingBp;
            if ($votingBp <= 0 && $rawEconomicBp <= 0) {
                continue;
            }

            $this->createPosition($offering, $equityClassId, (int) $userId, 'USER', 'LOCAL_INVESTOR', $economicBp, true, $votingBp);
            $this->recordFundingEvent($offering, 'equity.allocated', [
                'user_id' => (int) $userId,
                'equity_pct' => $economicBp / 100,
                'metadata' => [
                    'idempotency_key' => "equity:allocated:{$userId}",
                    'voting_bp' => $votingBp,
                    'economic_bp' => $economicBp,
                ],
            ]);

            if (($cap['excess_bp'][$userId] ?? 0) > 0) {
                $this->recordFundingEvent($offering, 'equity.capped', [
                    'user_id' => (int) $userId,
                    'equity_pct' => $cap['excess_bp'][$userId] / 100,
                    'metadata' => [
                        'idempotency_key' => "equity:capped:{$userId}",
                        'max_voting_equity_bp' => (int) $offering->max_local_voting_equity_bp,
                        'excess_loan_cents' => $cap['excess_loan_cents'][$userId] ?? 0,
                    ],
                ]);
            }
        }

        $treasuryBp = max(0, $poolBp - array_sum($cap['voting_bp']));
        if ($treasuryBp > 0) {
            $this->createPosition($offering, $equityClassId, null, 'TREASURY', 'TREASURY', $treasuryBp, false);
            $this->recordFundingEvent($offering, 'equity.treasury', [
                'equity_pct' => $treasuryBp / 100,
                'metadata' => ['idempotency_key' => 'equity:treasury', 'voting_bp' => 0, 'economic_bp' => $treasuryBp],
            ]);
        }

        return ['excess_loan_cents' => $cap['excess_loan_cents']];
    }

    private function createPosition(Offering $offering, int $classId, ?int $userId, string $holderType, string $positionType, int $economicBp, bool $votes, ?int $votingBp = null): void
    {
        $attributes = [
            'offering_id' => $offering->id,
            'equity_class_id' => $classId,
            'holder_user_id' => $userId,
            'holder_type' => $holderType,
            'pct_ownership' => number_format($economicBp / 100, 4, '.', ''),
            'source' => $positionType === 'ORG_RESERVE' ? 'ORG_RESERVE' : 'INVESTOR_POOL',
            'position_type' => $positionType,
            'voting_rights' => $votes,
            'can_vote' => $votes && ($votingBp ?? $economicBp) > 0,
            'voting_bp' => $votes ? ($votingBp ?? $economicBp) : 0,
            'economic_bp' => $economicBp,
        ];

        if (Schema::hasColumn('equity_positions', 'equity_class')) {
            $attributes['equity_class'] = $classId;
        }

        EquityPosition::create($attributes);
    }

    private function largestRemainderBp(array $amountsByUser, int $poolBp): array
    {
        $total = array_sum($amountsByUser);
        if ($total <= 0 || $poolBp <= 0) {
            return [];
        }

        $floors = [];
        $remainders = [];
        $allocated = 0;

        foreach ($amountsByUser as $userId => $cents) {
            $numerator = $cents * $poolBp;
            $floor = intdiv($numerator, $total);
            $floors[$userId] = $floor;
            $remainders[$userId] = $numerator - ($floor * $total);
            $allocated += $floor;
        }

        arsort($remainders);
        $remaining = $poolBp - $allocated;
        foreach (array_keys($remainders) as $userId) {
            if ($remaining <= 0) {
                break;
            }
            $floors[$userId]++;
            $remaining--;
        }

        return array_filter($floors);
    }
}
