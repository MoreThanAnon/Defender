<?php
namespace App\Jobs;
use App\Models\Offering;
use App\Services\Decidim\DecidimGovernanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class CreateDecidimGovernanceSpace implements ShouldQueue { use Dispatchable, InteractsWithQueue, Queueable, SerializesModels; public function __construct(public int $offeringId) {} public function handle(DecidimGovernanceService $service): void { $service->createGovernanceForSettledOffering(Offering::findOrFail($this->offeringId)); } }
