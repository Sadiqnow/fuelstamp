<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNozzleRequest;
use App\Http\Resources\NozzleResource;
use App\Models\Nozzle;
use App\Models\Pump;
use Illuminate\Http\JsonResponse;

class NozzleController extends Controller
{
    public function store(
        StoreNozzleRequest $request,
        Pump $pump
    ): JsonResponse {
        $this->ensurePumpScope($pump);

        $exists = Nozzle::where('pump_id', $pump->id)
            ->where('nozzle_code', $request->nozzle_code)
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => [
                    'code' => 'NOZZLE_CODE_EXISTS',
                    'message' => 'Nozzle code already exists for this pump.',
                    'details' => [],
                ],
            ], 422);
        }

        $nozzle = Nozzle::create([
            'tenant_id' => $pump->tenant_id,
            'pump_id' => $pump->id,
            'nozzle_code' => $request->nozzle_code,
            'fuel_product_id' => $request->fuel_product_id,
            'status' => 'ACTIVE',
        ]);

        $nozzle->load('fuelProduct');

        return response()->json([
            'data' => new NozzleResource($nozzle),
        ], 201);
    }

    private function ensurePumpScope(Pump $pump): void
    {
        $user = request()->user();

        if ($user->hasRole('PLATFORM_ADMIN')) {
            return;
        }

        abort_if(
            $pump->tenant_id !== $user->tenant_id,
            403,
            'You do not have access to this pump.'
        );
    }
}