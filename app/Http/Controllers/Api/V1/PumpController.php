<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePumpRequest;
use App\Http\Resources\PumpResource;
use App\Models\Pump;
use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PumpController extends Controller
{
    public function index(Station $station): AnonymousResourceCollection
    {
        $this->ensureStationScope($station);

        $pumps = $station->pumps()
            ->with('nozzles.fuelProduct')
            ->orderBy('pump_code')
            ->get();

        return PumpResource::collection($pumps);
    }

    public function store(
        StorePumpRequest $request,
        Station $station
    ): JsonResponse {
        $this->ensureStationScope($station);

        $exists = Pump::where('station_id', $station->id)
            ->where('pump_code', $request->pump_code)
            ->exists();

        if ($exists) {
            return response()->json([
                'error' => [
                    'code' => 'PUMP_CODE_EXISTS',
                    'message' => 'Pump code already exists for this station.',
                    'details' => [],
                ],
            ], 422);
        }

        $pump = Pump::create([
            'tenant_id' => $station->tenant_id,
            'station_id' => $station->id,
            'pump_code' => $request->pump_code,
            'status' => 'ACTIVE',
        ]);

        return response()->json([
            'data' => new PumpResource($pump),
        ], 201);
    }

    private function ensureStationScope(Station $station): void
    {
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