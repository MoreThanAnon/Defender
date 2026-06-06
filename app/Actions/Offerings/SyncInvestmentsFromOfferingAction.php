<?php

namespace App\Actions\Offerings;

use App\Models\Investment;
use App\Models\Offering;
use Illuminate\Support\Facades\Schema;

class SyncInvestmentsFromOfferingAction
{
    public function handle(Offering $offering): void
    {
        if (! Schema::hasTable('investments')) {
            return;
        }

        $targetCents = (int) ($offering->target_raise_cents ?: round(((float) $offering->target_raise) * 100));

        foreach ($offering->equityPositions()->where('position_type', 'LOCAL_INVESTOR')->get() as $position) {
            if (! $position->holder_user_id || $position->economic_bp <= 0) {
                continue;
            }

            $amount = round(($targetCents * (int) $position->economic_bp) / 10000 / 100, 2);

            Investment::updateOrCreate(
                [
                    'user_id' => $position->holder_user_id,
                    'property_id' => $offering->property_id,
                    'type' => 'equity',
                ],
                [
                    'amount' => $amount,
                    'status' => 'active',
                    'interest_rate' => null,
                ]
            );
        }

        foreach ($offering->loanNotes()->get() as $note) {
            $userId = $note->lender_user_id ?: $note->investor_id;
            if (! $userId) {
                continue;
            }

            $principalCents = (int) ($note->principal_cents ?: round(((float) $note->principal) * 100));

            Investment::updateOrCreate(
                [
                    'user_id' => $userId,
                    'property_id' => $offering->property_id,
                    'type' => 'loan',
                ],
                [
                    'amount' => round($principalCents / 100, 2),
                    'status' => 'active',
                    'interest_rate' => round(((int) $note->interest_rate_bp) / 100, 2),
                ]
            );
        }
    }
}
