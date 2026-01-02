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
            ->whereRaw('(SELECT SUM(amount) FROM bids WHERE bids.offering_id = offerings.id AND bids.status IN ("PLEDGED","ACCEPTED")) >= target_raise')
            ->get();

        if ($offerings->isEmpty()) {
            $this->info('No offerings to settle.');
            return 0;
        }

        $svc = new EquityAllocationService();

        foreach ($offerings as $offering) {
            $this->info("Settling offering: {$offering->id} - {$offering->name}");

            // Find the equity class (adjust as needed for your logic)
            $equityClass = EquityClass::where('hotel_id', $offering->hotel_id)->first();
            if (!$equityClass) {
                $this->error("No equity class found for offering {$offering->id}");
                continue;
            }

            try {
                $settler = new SettleOffering($svc);
                $settler->handle($offering, $equityClass->id, null);
                $this->info("Offering {$offering->id} settled.");
            } catch (\Throwable $e) {
                $this->error("Failed to settle offering {$offering->id}: " . $e->getMessage());
            }
        }

        return 0;
    }
}