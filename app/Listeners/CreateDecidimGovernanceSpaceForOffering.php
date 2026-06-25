<?php
namespace App\Listeners;
use App\Events\OfferingSettled;
use App\Jobs\CreateDecidimGovernanceSpace;
class CreateDecidimGovernanceSpaceForOffering { public function handle(OfferingSettled $event): void { CreateDecidimGovernanceSpace::dispatch($event->offering->id); } }
