<?php

namespace App\Services;

use App\Models\Offering;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DecidimIntegrationService
{
    public function syncOfferingGovernance(Offering $offering): array
    {
        $this->guardConfig();

        $payload = $this->buildOfferingPayload($offering);
        $endpoint = $this->endpoint('/api/v1/offerings/sync');

        $response = Http::withToken(Config::get('decidim.api_token'))
            ->acceptJson()
            ->post($endpoint, $payload);

        if ($response->failed()) {
            Log::warning('Decidim sync failed', [
                'offering_id' => $offering->id,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('Decidim sync failed: ' . $response->body());
        }

        return $response->json();
    }

    public function buildOfferingPayload(Offering $offering): array
    {
        $equityPositions = $offering->equityPositions()->with('holder')->get()->map(function ($position) {
            return [
                'holder_type' => $position->holder_type,
                'holder_user_id' => $position->holder_user_id,
                'holder_name' => $position->holder?->username,
                'pct_ownership' => (string) $position->pct_ownership,
                'source' => $position->source,
                'equity_class' => $position->equityClass?->code,
                'voting_rights' => $position->equityClass?->voting_rights,
            ];
        })->all();

        return [
            'offering_id' => $offering->id,
            'offering_name' => $offering->name,
            'property_id' => $offering->property_id,
            'property_name' => $offering->property?->name,
            'campaign_end' => optional($offering->campaign_end)->toIso8601String(),
            'equity_positions' => $equityPositions,
            'decidim_space_id' => Config::get('decidim.space_id'),
            'decidim_assembly_id' => Config::get('decidim.assembly_id'),
        ];
    }

    private function endpoint(string $path): string
    {
        return rtrim(Config::get('decidim.base_url'), '/') . '/' . ltrim($path, '/');
    }

    private function guardConfig(): void
    {
        if (! Config::get('decidim.base_url') || ! Config::get('decidim.api_token')) {
            throw new RuntimeException('Decidim configuration is missing. Set DECIDIM_BASE_URL and DECIDIM_API_TOKEN.');
        }
    }
}
