<?php
namespace App\Jobs;
use App\Models\DecidimGovernanceSpace;
use App\Models\Offering;
use App\Services\Decidim\DecidimGovernanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class CreateDecidimGovernanceSpace implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(public int $offeringId) {}

    public function handle(DecidimGovernanceService $service): void
    {
        $service->createGovernanceForSettledOffering(Offering::findOrFail($this->offeringId));
    }

    public function failed(Throwable $exception): void
    {
        $offering = Offering::find($this->offeringId);

        if (! $offering) {
            return;
        }

        DecidimGovernanceSpace::updateOrCreate(
            ['offering_id' => $offering->id],
            [
                'property_id' => $offering->property_id,
                'status' => 'failed',
                'last_failed_at' => now(),
                'error_message' => $exception->getMessage(),
            ]
        );
    }
}
