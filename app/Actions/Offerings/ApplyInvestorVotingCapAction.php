<?php

namespace App\Actions\Offerings;

use App\Models\Offering;

class ApplyInvestorVotingCapAction
{
    public function handle(Offering $offering, array $investorBp, array $acceptedLocalCents): array
    {
        $capBp = (int) ($offering->max_local_voting_equity_bp ?: 1500);
        $totalLocalCents = array_sum($acceptedLocalCents);
        $cappedBp = [];
        $excessBp = [];
        $excessLoanCents = [];

        foreach ($investorBp as $userId => $bp) {
            $cappedBp[$userId] = min($bp, $capBp);
            $excessBp[$userId] = max(0, $bp - $capBp);

            if ($excessBp[$userId] > 0 && $totalLocalCents > 0) {
                $votingEligibleCents = (int) floor($totalLocalCents * $capBp / max(1, $this->investorPoolBp($offering)));
                $excessLoanCents[$userId] = max(0, ($acceptedLocalCents[$userId] ?? 0) - $votingEligibleCents);
            }
        }

        return [
            'voting_bp' => $cappedBp,
            'excess_bp' => $excessBp,
            'excess_loan_cents' => array_filter($excessLoanCents),
            'total_capped_bp' => array_sum($cappedBp),
        ];
    }

    private function investorPoolBp(Offering $offering): int
    {
        return max(0, 10000 - (int) round(((float) $offering->org_reserve_pct) * 100));
    }
}
