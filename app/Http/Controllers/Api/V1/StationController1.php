<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStationRequest;
use App\Http\Requests\UpdateStationRequest;
use App\Http\Resources\StationResource;
use App\Models\Station;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class StationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return StationResource::collection(
            $this->visibleStations($request)
                ->orderBy('name')
                ->paginate(15)
        );
    }

    public function store(StoreStationRequest $request): JsonResponse
    {
        $user = $request->user();
        $tenantId = $user->hasRole('PLATFORM_ADMIN')
            ? $request->validated('tenant_id')
            : $user->tenant_id;

        abort_if($tenantId === null, 403, 'A tenant is required to create a station.');

        $station = Station::create(array_merge(
            $request->validated(),
            ['tenant_id' => $tenantId]
        ));

        return (new StationResource($station))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, string $station): StationResource
    {
        return new StationResource(
            $this->visibleStations($request)->findOrFail($station)
        );
    }

    public function update(UpdateStationRequest $request, string $station): StationResource
    {
        $record = $this->visibleStations($request)->findOrFail($station);
        $record->update($request->validated());

        return new StationResource($record);
    }

    public function destroy(Request $request, string $station): Response
    {
        $this->visibleStations($request)
            ->findOrFail($station)
            ->delete();

        return response()->noContent();
    }

    private function visibleStations(Request $request): Builder
    {
        $query = Station::query();
        $user = $request->user();

        if (! $user->hasRole('PLATFORM_ADMIN')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        return $query;
    }
}
