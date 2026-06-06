<?php

namespace App\Actions\Offerings;

use App\Models\LoanNote;
use App\Models\Offering;
use Illuminate\Support\Carbon;

class CreateLoanNotesAction
{
    use RecordsFundingEvents;

    public function handle(Offering $offering, array $extraLocalLoanCents = []): void
    {
        $nonLocal = $offering->bids()
            ->where('accepted_amount_cents', '>', 0)
            ->get()
            ->reject(fn ($bid) => (bool) ($bid->is_local_at_bid_time ?? $bid->is_local))
            ->groupBy('user_id')
            ->map(fn ($bids) => $bids->sum('accepted_amount_cents'))
            ->all();

        $amounts = $nonLocal;
        foreach ($extraLocalLoanCents as $userId => $cents) {
            $amounts[$userId] = ($amounts[$userId] ?? 0) + $cents;
        }

        foreach (array_filter($amounts) as $userId => $principalCents) {
            if (LoanNote::where('offering_id', $offering->id)->where('investor_id', $userId)->exists()) {
                continue;
            }

            LoanNote::create([
                'offering_id' => $offering->id,
                'lender_user_id' => $userId,
                'investor_id' => $userId,
                'principal' => number_format($principalCents / 100, 2, '.', ''),
                'principal_cents' => $principalCents,
                'interest_rate_bp' => (int) $offering->default_loan_interest_rate_bp,
                'term_months' => (int) $offering->default_loan_term_months,
                'repayment_frequency' => $offering->default_loan_repayment_frequency,
                'maturity_date' => Carbon::now()->addMonths((int) $offering->default_loan_term_months)->toDateString(),
                'status' => 'active',
                'ranking' => $offering->default_loan_ranking,
                'early_repayment_allowed' => true,
                'early_repayment_fee_bp' => 0,
                'has_protective_votes' => true,
                'voting_scope' => 'debt_protective_only',
            ]);

            $this->recordFundingEvent($offering, 'loan_note.created', [
                'user_id' => (int) $userId,
                'amount' => $principalCents / 100,
                'metadata' => [
                    'idempotency_key' => "loan_note:{$userId}",
                    'principal_cents' => $principalCents,
                    'voting_scope' => 'debt_protective_only',
                ],
            ]);
        }
    }
}
