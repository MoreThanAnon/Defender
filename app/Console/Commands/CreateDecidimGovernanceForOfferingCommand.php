<?php
namespace App\Console\Commands;
use App\Models\Offering;
use App\Services\Decidim\DecidimGovernanceService;
use Illuminate\Console\Command;
class CreateDecidimGovernanceForOfferingCommand extends Command { protected $signature = 'decidim:create-governance-for-offering {offering_id}'; protected $description = 'Create the Decidim governance space for a settled offering'; public function handle(DecidimGovernanceService $service): int { $space = $service->createGovernanceForSettledOffering(Offering::findOrFail($this->argument('offering_id'))); $this->info($space->decidim_assembly_url ?: 'Governance space '.$space->status); return self::SUCCESS; } }
