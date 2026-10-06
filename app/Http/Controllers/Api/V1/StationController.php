<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStationRequest;
use App\Http\Requests\UpdateStationRequest;
use App\Http\Resources\StationResource;
use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $user = request()->user();

        $query = Station::query();

        if (! $user->hasRole('PLATFORM_ADMIN')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        return StationResource::collection(
            $query->latest()->get()
        );
    }

    public function store(StoreStationRequest $request): JsonResponse
    {
        $user = $request->user();

        $station = Station::create([
            'tenant_id' => $user->tenant_id,
            'station_code' => $request->station_code,
            'name' => $request->name,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'license_expiry' => $request->license_expiry,
            'status' => 'PENDING',
        ]);

        return response()->json([
            'data' => new StationResource($station),
        ], 201);
    }

    public function show(Station $station): StationResource
    {
        $this->ensureStationScope($station);

        return new StationResource($station);
    }

    public function update(
        UpdateStationRequest $request,
        Station $station
    ): StationResource {
        $this->ensureStationScope($station);

        $station->update(
            $request->validated()
        );

        return new StationResource(
            $station->fresh()
        );
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