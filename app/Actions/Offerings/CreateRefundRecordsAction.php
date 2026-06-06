<?php

namespace App\Actions\Offerings;

use App\Models\Offering;
use App\Models\RefundRecord;

class CreateRefundRecordsAction
{
    use RecordsFundingEvents;

    public function handle(Offering $offering, ?array $refundsByBidId = null): void
    {
        $bids = $offering->bids()->get();
        $refundsByBidId ??= $bids
            ->filter(fn ($bid) => (int) $bid->refunded_amount_cents > 0)
            ->mapWithKeys(fn ($bid) => [$bid->id => (int) $bid->refunded_amount_cents])
            ->all();

        foreach ($bids as $bid) {
            $amountCents = (int) ($refundsByBidId[$bid->id] ?? 0);
            if ($amountCents <= 0) {
                continue;
            }

            RefundRecord::firstOrCreate(
                ['offering_id' => $offering->id, 'bid_id' => $bid->id],
                [
                    'user_id' => $bid->user_id,
                    'amount_cents' => $amountCents,
                    'status' => 'pending',
                    'metadata' => ['reason' => $bid->allocation_status],
                ]
            );

            $bid->forceFill([
                'refunded_amount_cents' => $amountCents,
                'allocation_status' => 'refund_pending',
            ])->save();

            $this->recordFundingEvent($offering, 'refund.required', [
                'user_id' => $bid->user_id,
                'amount' => $amountCents / 100,
                'metadata' => [
                    'idempotency_key' => "refund:required:bid:{$bid->id}",
                    'bid_id' => $bid->id,
                    'amount_cents' => $amountCents,
                ],
            ]);
        }
    }
}
