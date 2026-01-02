<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Offering;
use App\Models\EquityClass;
use App\Actions\Offerings\SettleOffering;
use App\Services\EquityAllocationService;
use Carbon\Carbon;

class SettleOfferingsCommand extends Command
{
    protected $signature = 'offerings:settle';
    protected $description = 'Settle all closed offerings where the campaign has ended and the target was reached';

    public function handle()
    {
        $now = Carbon::now();

        // Find all offerings that are CLOSED, campaign_end has passed, and target_raise met
        $offerings = Offering::where('status', 'CLOSED')
            ->whereNotNull('campaign_end')
            ->where('campaign_end', '<=', $now)
            // Only select offerings where the sum of all accepted or pledged bids for this property
            // meets or exceeds the target raise amount (i.e., the funding goal has been reached)
            ->whereRaw('(SELECT SUM(amount) FROM bids WHERE bids.property_id = offerings.id AND bids.status IN ("PLEDGED","ACCEPTED")) >= target_raise')
            ->get();

        if ($offerings->isEmpty()) {
            $this->info('No offerings to settle.');
            return 0;
        }
        
        $svc = new EquityAllocationService();
        //Each offer is a campaign to buy a property
        foreach ($offerings as $offering) {
            //Give the equity and loan notes to the investors for this offering/campaign
            $this->info("Settling offering: {$offering->id} - {$offering->name}");

            // Find the equity class (adjust as needed for your logic)
            $equityClass = "ORD-A";

            try {
                $settler = new SettleOffering($svc);
                $settler->handle($offering, $equityClass, null);
                $this->info("Offering {$offering->id} settled.");
            } catch (\Throwable $e) {
                $this->error("Failed to settle offering {$offering->id}: " . $e->getMessage());
            }
        }

        return 0;
    }
}