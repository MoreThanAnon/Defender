<?php

namespace App\Actions\Offerings;

use App\Models\FundingEvent;
use App\Models\Offering;

trait RecordsFundingEvents
{
    private function recordFundingEvent(Offering $offering, string $eventType, array $attributes = []): FundingEvent
    {
        $metadata = $attributes['metadata'] ?? [];
        $key = $metadata['idempotency_key'] ?? null;

        if ($key) {
            $existing = FundingEvent::where('offering_id', $offering->id)
                ->where('event_type', $eventType)
                ->where('metadata->idempotency_key', $key)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return FundingEvent::create(array_merge($attributes, [
            'offering_id' => $offering->id,
            'event_type' => $eventType,
            'metadata' => $metadata,
        ]));
    }
}
