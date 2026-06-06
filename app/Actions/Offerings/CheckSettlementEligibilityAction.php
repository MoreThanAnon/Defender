<?php

namespace App\Actions\Offerings;

use App\Models\Offering;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class CheckSettlementEligibilityAction
{
    public function handle(Offering $offering, ?CarbonInterface $now = null): array
    {
        $now ??= Carbon::now();

        $validBids = $offering->bids()->whereIn('status', ['PLEDGED', 'ACCEPTED'])->get();
        $targetCents = $this->targetCents($offering);
        $validTotal = $validBids->sum(fn ($bid) => $this->bidOriginalCents($bid));
        $localBids = $validBids->filter(fn ($bid) => $this->isLocal($bid));
        $localTotal = $localBids->sum(fn ($bid) => $this->bidOriginalCents($bid));
        $requiredLocal = (int) ceil($targetCents * ((int) $offering->min_local_capital_pct ?: 0) / 100);
        $localInvestorCount = $localBids->pluck('user_id')->unique()->count();
        $requiresLocal = (bool) $offering->require_local_threshold_for_settlement;

        $reasons = [];

        if ($offering->status !== 'CLOSED') {
            $reasons[] = 'not_closed';
        }

        if (! $offering->campaign_end || $offering->campaign_end->gt($now)) {
            $reasons[] = 'campaign_not_ended';
        }

        if ($validTotal < $targetCents) {
            $reasons[] = 'under_target';
        }

        if ($requiresLocal && $localTotal < $requiredLocal) {
            $reasons[] = 'below_local_capital_floor';
        }

        if ($requiresLocal && $localInvestorCount < (int) $offering->min_local_investor_count) {
            $reasons[] = 'below_local_investor_floor';
        }

        return [
            'eligible' => $reasons === [],
            'reasons' => $reasons,
            'failure_reason' => $this->failureReason($reasons),
            'valid_total_cents' => $validTotal,
            'target_cents' => $targetCents,
            'local_total_cents' => $localTotal,
            'required_local_cents' => $requiredLocal,
            'local_investor_count' => $localInvestorCount,
        ];
    }

    private function failureReason(array $reasons): ?string
    {
        foreach (['under_target', 'below_local_capital_floor', 'below_local_investor_floor'] as $reason) {
            if (in_array($reason, $reasons, true)) {
                return $reason;
            }
        }

        return null;
    }

    private function targetCents(Offering $offering): int
    {
        return (int) ($offering->target_raise_cents ?: round(((float) $offering->target_raise) * 100));
    }

    private function bidOriginalCents($bid): int
    {
        return (int) ($bid->original_amount_cents ?: round(((float) $bid->amount) * 100));
    }

    private function isLocal($bid): bool
    {
        return (bool) ($bid->is_local_at_bid_time ?? $bid->is_local);
    }
}
