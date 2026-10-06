<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTankRequest;
use App\Http\Resources\TankResource;
use App\Models\Station;
use App\Models\Tank;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TankController extends Controller
{
    public function index(
        Station $station
    ): AnonymousResourceCollection {
        $this->ensureStationScope($station);

        $tanks = $station->tanks()
            ->with('fuelProduct')
            ->orderBy('tank_code')
            ->get();

        return TankResource::collection($tanks);
    }

    public function store(
        StoreTankRequest $request,
        Station $station
    ): JsonResponse {
        $this->ensureStationScope($station);

        $exists = Tank::query()
            ->where('station_id', $station->id)
            ->where('tank_code', $request->tank_code)
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => [
                    'code' => 'TANK_CODE_EXISTS',
                    'message' =>
                        'Tank code already exists for this station.',
                    'details' => [],
                ],
            ], 422);
        }

        $tank = Tank::create([
            'tenant_id' =>
                $station->tenant_id,

            'station_id' =>
                $station->id,

            'tank_code' =>
                $request->tank_code,

            'fuel_product_id' =>
                $request->fuel_product_id,

            'capacity_litres' =>
                $request->capacity_litres,

            'low_level_threshold_litres' =>
                $request->low_level_threshold_litres ?? 0,

            'status' =>
                'ACTIVE',
        ]);

        $tank->load('fuelProduct');

        return response()->json([
            'data' => new TankResource($tank),
        ], 201);
    }

    private function ensureStationScope(
        Station $station
    ): void {
        $user = request()->user();

        if ($user->hasRole('PLATFORM_ADMIN')) {
            return;
        }

        abort_if(
            $station->tenant_id !== $user->tenant_id,
            403,
            'You do not have access to this station.'
        );
    }
}