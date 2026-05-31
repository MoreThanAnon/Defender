<?php

namespace App\Console\Commands;

use App\Actions\Offerings\SettleOffering;
use App\Models\EquityClass;
use App\Models\Offering;
use App\Services\DecidimIntegrationService;
use App\Services\EquityAllocationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SettleOfferingsCommand extends Command
{
    protected $signature = 'offerings:settle';
    protected $description = 'Settle all closed offerings where the campaign has ended and the target was reached';

    public function handle()
    {
        $now = Carbon::now();

        $offerings = Offering::where('status', 'CLOSED')
            ->whereNotNull('campaign_end')
            ->where('campaign_end', '<=', $now)
            ->whereRaw('(SELECT COALESCE(SUM(amount), 0) FROM bids WHERE bids.offering_id = offerings.id AND bids.status IN ("PLEDGED","ACCEPTED")) >= target_raise')
            ->get();

        if ($offerings->isEmpty()) {
            $this->info('No offerings to settle.');

            return 0;
        }

        $svc = new EquityAllocationService();
        $decidim = new DecidimIntegrationService();

        foreach ($offerings as $offering) {
            $this->info("Settling offering: {$offering->id} - {$offering->name}");

            $equityClass = EquityClass::firstOrCreate(
                ['property_id' => $offering->property_id, 'code' => 'ORD-A'],
                ['name' => 'Ordinary A']
            );

            try {
                $settler = new SettleOffering($svc);
                $settler->handle($offering, $equityClass->id, null);
                $this->info("Offering {$offering->id} settled.");

                try {
                    $decidim->syncOfferingGovernance($offering);
                    $this->info("Offering {$offering->id} synced to Decidim.");
                } catch (\Throwable $e) {
                    $this->warn("Decidim sync failed for offering {$offering->id}: " . $e->getMessage());
                }
            } catch (\Throwable $e) {
                $this->error("Failed to settle offering {$offering->id}: " . $e->getMessage());
            }
        }

        return 0;
    }
}
