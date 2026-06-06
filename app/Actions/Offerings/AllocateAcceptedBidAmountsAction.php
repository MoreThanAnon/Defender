<?php

namespace App\Actions\Offerings;

use App\Models\Bid;
use App\Models\Offering;

class AllocateAcceptedBidAmountsAction
{
    use RecordsFundingEvents;

    public function handle(Offering $offering): array
    {
        $bids = $offering->bids()->whereIn('status', ['PLEDGED', 'ACCEPTED'])->orderBy('id')->get();
        $cap = (int) ($offering->hard_cap_cents ?: $offering->target_raise_cents ?: round(((float) $offering->target_raise) * 100));

        $remaining = $cap;
        $accepted = [];
        $refunds = [];

        foreach ([true, false] as $localBucket) {
            $bucket = $bids->filter(fn ($bid) => $this->isLocal($bid) === $localBucket);
            $bucketTotal = $bucket->sum(fn ($bid) => $this->originalCents($bid));

            if ($bucketTotal <= 0 || $remaining <= 0) {
                foreach ($bucket as $bid) {
                    $this->updateBidAllocation($offering, $bid, 0, $this->originalCents($bid));
                    $refunds[$bid->id] = $this->originalCents($bid);
                }
                continue;
            }

            $bucketAllocation = min($remaining, $bucketTotal);
            $allocations = $bucketTotal > $bucketAllocation
                ? $this->proRata($bucket->all(), $bucketAllocation)
                : $bucket->mapWithKeys(fn ($bid) => [$bid->id => $this->originalCents($bid)])->all();

            foreach ($bucket as $bid) {
                $acceptedCents = (int) ($allocations[$bid->id] ?? 0);
                $refundCents = max(0, $this->originalCents($bid) - $acceptedCents);
                $this->updateBidAllocation($offering, $bid, $acceptedCents, $refundCents);
                $accepted[$bid->id] = $acceptedCents;
                if ($refundCents > 0) {
                    $refunds[$bid->id] = $refundCents;
                }
            }

            $remaining -= $bucketAllocation;
        }

        return ['accepted' => $accepted, 'refunds' => $refunds, 'cap_cents' => $cap];
    }

    private function updateBidAllocation(Offering $offering, Bid $bid, int $acceptedCents, int $refundCents): void
    {
        $original = $this->originalCents($bid);
        $allocationStatus = match (true) {
            $acceptedCents === $original => 'accepted_full',
            $acceptedCents > 0 => 'accepted_partial',
            default => 'rejected_excess',
        };

        $bid->forceFill([
            'original_amount_cents' => $original,
            'accepted_amount_cents' => $acceptedCents,
            'refunded_amount_cents' => $refundCents,
            'allocation_status' => $allocationStatus,
            'status' => $acceptedCents > 0 ? 'ACCEPTED' : $bid->status,
        ])->save();

        $this->recordFundingEvent($offering, 'bid.' . $allocationStatus, [
            'user_id' => $bid->user_id,
            'amount' => $acceptedCents / 100,
            'metadata' => [
                'idempotency_key' => "bid:{$bid->id}:{$allocationStatus}",
                'bid_id' => $bid->id,
                'original_amount_cents' => $original,
                'accepted_amount_cents' => $acceptedCents,
                'refunded_amount_cents' => $refundCents,
            ],
        ]);
    }

    private function proRata(array $bids, int $availableCents): array
    {
        $total = array_sum(array_map(fn ($bid) => $this->originalCents($bid), $bids));
        $floors = [];
        $remainders = [];
        $allocated = 0;

        foreach ($bids as $bid) {
            $numerator = $this->originalCents($bid) * $availableCents;
            $floor = intdiv($numerator, $total);
            $floors[$bid->id] = $floor;
            $remainders[$bid->id] = $numerator - ($floor * $total);
            $allocated += $floor;
        }

        arsort($remainders);
        $remaining = $availableCents - $allocated;
        foreach (array_keys($remainders) as $bidId) {
            if ($remaining <= 0) {
                break;
            }
            $floors[$bidId]++;
            $remaining--;
        }

        return $floors;
    }

    private function originalCents(Bid $bid): int
    {
        return (int) ($bid->original_amount_cents ?: round(((float) $bid->amount) * 100));
    }

    private function isLocal(Bid $bid): bool
    {
        return (bool) ($bid->is_local_at_bid_time ?? $bid->is_local);
    }
}
