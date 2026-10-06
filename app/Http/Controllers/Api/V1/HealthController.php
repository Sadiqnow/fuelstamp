<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => [
                'service' => 'FuelStamp API',
                'version' => 'v1',
                'status' => 'ok',
            ],
            'meta' => [
                'environment' => app()->environment(),
            ],
        ]);
    }
}