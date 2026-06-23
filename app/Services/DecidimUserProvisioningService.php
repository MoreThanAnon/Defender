<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DecidimUserProvisioningService
{
    public function provision(User $user): array
    {
        $token = Config::get('decidim.property_governance_token');
        $baseUrl = Config::get('decidim.base_url');

        if (! $token || ! $baseUrl) {
            throw new RuntimeException('Decidim provisioning is not configured.');
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->post(rtrim($baseUrl, '/').'/api/property_governance/users', [
                'laravel_user_id' => (string) $user->getKey(),
                'email' => $user->email,
                'name' => $user->real_name ?: $user->display_name ?: $user->username,
                'nickname' => $user->username,
            ]);

        if ($response->failed() || ! ($response->json('success') ?? false)) {
            throw new RuntimeException('Decidim participant provisioning failed.');
        }

        return $response->json();
    }
}
