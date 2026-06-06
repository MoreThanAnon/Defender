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
            $role = $position->position_type === 'ORG_RESERVE' ? 'org_steward' : 'local_equity_voter';
            $canVote = $role === 'local_equity_voter' && (bool) $position->can_vote;

            return [
                'offering_id' => $position->offering_id,
                'investor_id' => $position->holder_user_id,
                'decidim_user_id' => $position->holder_user_id ? (string) $position->holder_user_id : null,
                'role' => $role,
                'can_vote' => $canVote,
                'voting_bp' => $canVote ? (int) $position->voting_bp : 0,
                'voting_scope' => $canVote ? 'ordinary_property_control' : 'reserved_matter_only',
                'holder_name' => $position->holder?->username,
            ];
        })->all();

        $loanNoteParticipants = $offering->loanNotes()->get()->map(function ($note) use ($offering) {
            $investorId = $note->investor_id ?: $note->lender_user_id;

            return [
                'offering_id' => $offering->id,
                'investor_id' => $investorId,
                'decidim_user_id' => $investorId ? (string) $investorId : null,
                'role' => 'non_voting_lender',
                'can_vote' => false,
                'voting_bp' => 0,
                'voting_scope' => $note->voting_scope ?: 'debt_protective_only',
            ];
        })->all();

        return [
            'offering_id' => $offering->id,
            'offering_name' => $offering->name,
            'property_id' => $offering->property_id,
            'property_name' => $offering->property?->name,
            'campaign_end' => optional($offering->campaign_end)->toIso8601String(),
            'participants' => array_values(array_merge($equityPositions, $loanNoteParticipants)),
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
