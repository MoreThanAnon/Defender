<?php

namespace App\Http\Controllers;

use App\Models\Offering;
use App\Services\DecidimIntegrationService;
use Illuminate\Http\JsonResponse;

class DecidimController extends Controller
{
    public function syncOffering(Offering $offering, DecidimIntegrationService $decidim): JsonResponse
    {
        try {
            $result = $decidim->syncOfferingGovernance($offering);

            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
