<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DecidimPocService
{
    public function run(): array
    {
        $startedAt = now();

        $apiSignIn = $this->apiSignIn();
        $organization = $this->fetchOrganization();
        $assembliesBefore = $this->fetchAssemblies();
        $createdAssembly = $this->createAssembly();
        $assembliesAfter = $this->fetchAssemblies();

        return [
            'success' => true,
            'message' => 'Connected to Decidim and created/found the test assembly.',
            'decidim' => [
                'base_url' => $this->baseUrl(),
                'version' => '0.31.5',
                'graphql_endpoint' => $this->endpoint('/api/'),
                'api_sign_in_endpoint' => $this->endpoint('/api/sign_in'),
                'assembly_creation_endpoint' => $this->endpoint('/api/property_governance/assemblies'),
                'assembly_creation_method' => 'Authenticated HTTP endpoint inside Decidim.',
            ],
            'api_sign_in' => $apiSignIn,
            'organization' => $organization,
            'assemblies_before' => $assembliesBefore,
            'created_assembly' => $createdAssembly,
            'assemblies_after' => $assembliesAfter,
            'duration_ms' => $startedAt->diffInMilliseconds(now()),
        ];
    }

    public function fetchOrganization(): array
    {
        $data = $this->graphql('{ organization { name { translation(locale: "en") } } }');

        return [
            'name' => Arr::get($data, 'organization.name.translation'),
            'host' => parse_url($this->baseUrl(), PHP_URL_HOST),
        ];
    }

    public function fetchAssemblies(): array
    {
        $data = $this->graphql('{ assemblies { id title { translation(locale: "en") } } }');

        return collect(Arr::get($data, 'assemblies', []))
            ->map(fn (array $assembly) => [
                'id' => $assembly['id'] ?? null,
                'title' => Arr::get($assembly, 'title.translation'),
            ])
            ->values()
            ->all();
    }

    public function apiSignIn(): array
    {
        if (! Config::get('decidim.api_key') || ! Config::get('decidim.api_secret')) {
            return [
                'success' => false,
                'status' => null,
                'note' => 'DECIDIM_API_KEY and DECIDIM_API_SECRET are not configured.',
            ];
        }

        $response = Http::acceptJson()
            ->asJson()
            ->timeout(15)
            ->post($this->endpoint('/api/sign_in'), [
                'api_user' => [
                    'key' => Config::get('decidim.api_key'),
                    'secret' => Config::get('decidim.api_secret'),
                ],
            ]);

        $json = $response->json();
        $token = Arr::get($json, 'jwt_token');

        return [
            'success' => $response->ok() && filled($token),
            'status' => $response->status(),
            'jwt_token_present' => filled($token),
            'body' => $json ?: $response->body(),
            'note' => filled($token)
                ? 'API user JWT authentication is working.'
                : 'Decidim accepted the request shape but did not return a JWT; assembly creation uses the shared-token property governance HTTP endpoint.',
        ];
    }

    public function createAssembly(): array
    {
        $token = Config::get('decidim.property_governance_token');

        if (! $token) {
            throw new RuntimeException('DECIDIM_PROPERTY_GOVERNANCE_TOKEN is missing.');
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout(20)
            ->post($this->endpoint('/api/property_governance/assemblies'), [
                'title' => 'TEST HOTEL GOVERNANCE',
                'slug' => 'test-asset-governance',
                'description' => 'Created from Laravel POC connection test.',
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Decidim assembly endpoint failed: '.$response->body());
        }

        $decoded = $response->json();

        if (! is_array($decoded) || ! ($decoded['success'] ?? false)) {
            throw new RuntimeException('Decidim assembly endpoint returned unexpected response: '.$response->body());
        }

        return $decoded;
    }

    private function graphql(string $query): array
    {
        $response = Http::acceptJson()
            ->asJson()
            ->timeout(20)
            ->post($this->endpoint('/api/'), ['query' => $query]);

        if ($response->failed()) {
            throw new RuntimeException('Decidim GraphQL request failed: '.$response->body());
        }

        $json = $response->json();

        if (isset($json['errors'])) {
            throw new RuntimeException('Decidim GraphQL returned errors: '.json_encode($json['errors']));
        }

        return $json['data'] ?? [];
    }

    private function endpoint(string $path): string
    {
        return rtrim($this->baseUrl(), '/').'/'.ltrim($path, '/');
    }

    private function baseUrl(): string
    {
        $baseUrl = Config::get('decidim.base_url');

        if (! $baseUrl) {
            throw new RuntimeException('DECIDIM_BASE_URL is missing.');
        }

        return $baseUrl;
    }
}
