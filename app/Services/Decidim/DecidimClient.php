<?php

namespace App\Services\Decidim;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class DecidimClient
{
    public function createAssembly(array $payload): array { return $this->post('/api/property_governance/assemblies', $payload); }
    public function syncUser(array $payload): array { return $this->post('/api/property_governance/users', $payload); }
    public function addAssemblyMember(string $assemblyId, array $payload): array { return $this->post("/api/property_governance/assemblies/{$assemblyId}/members", $payload); }
    public function createTaxonomy(array $payload): array { return $this->post('/api/property_governance/taxonomies', $payload); }
    public function createComponent(string $assemblyId, array $payload): array { return $this->post("/api/property_governance/assemblies/{$assemblyId}/components", $payload); }
    public function createDebate(string $componentId, array $payload): array { return $this->post("/api/property_governance/components/{$componentId}/debates", $payload); }
    public function createProposal(string $componentId, array $payload): array { return $this->post("/api/property_governance/components/{$componentId}/proposals", $payload); }
    public function createMeeting(string $componentId, array $payload): array { return $this->post("/api/property_governance/components/{$componentId}/meetings", $payload); }
    public function createVoteOrElectionComponent(string $assemblyId, array $payload): array { return $this->createComponent($assemblyId, $payload); }

    private function post(string $path, array $payload): array
    {
        $baseUrl = config('decidim.base_url');
        $token = config('decidim.api_token') ?: config('decidim.property_governance_token');
        if (! $baseUrl || ! $token) throw new RuntimeException('Decidim API is not configured.');
        $response = Http::withToken($token)->acceptJson()->asJson()->timeout(20)->post(rtrim($baseUrl, '/').$path, $payload);
        if ($response->failed()) throw new RuntimeException("Decidim API {$path} failed: ".$response->body());
        $data = $response->json('data') ?: $response->json();
        if (isset($data['assembly_id']) && ! isset($data['id'])) $data['id'] = (string) $data['assembly_id'];
        return $data;
    }
}
