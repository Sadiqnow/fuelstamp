<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShiftTemplateRequest;
use App\Http\Requests\UpdateShiftTemplateRequest;
use App\Http\Resources\ShiftTemplateResource;
use App\Models\ShiftTemplate;
use App\Models\Station;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShiftTemplateController extends Controller
{
    public function index(
        Station $station
    ): AnonymousResourceCollection {
        $this->ensureStationScope($station);

        $templates = $station
            ->shiftTemplates()
            ->orderBy('name')
            ->get();

        return ShiftTemplateResource::collection(
            $templates
        );
    }

    public function store(
        StoreShiftTemplateRequest $request,
        Station $station
    ): JsonResponse {
        $this->ensureStationScope($station);

        $template = ShiftTemplate::create([
            'tenant_id' =>
                $station->tenant_id,

            'station_id' =>
                $station->id,

            'name' =>
                $request->name,

            'duration_minutes' =>
                $request->duration_minutes,

            'break_minutes' =>
                $request->break_minutes ?? 0,

            'reconciliation_window_minutes' =>
                $request->reconciliation_window_minutes ?? 30,

            'active' =>
                true,
        ]);

        return response()->json([
            'data' =>
                new ShiftTemplateResource($template),
        ], 201);
    }

    public function update(
        UpdateShiftTemplateRequest $request,
        ShiftTemplate $shiftTemplate
    ): ShiftTemplateResource {
        $this->ensureTemplateScope($shiftTemplate);

        $shiftTemplate->update(
            $request->validated()
        );

        return new ShiftTemplateResource(
            $shiftTemplate->fresh()
        );
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

    private function ensureTemplateScope(
        ShiftTemplate $shiftTemplate
    ): void {
        $user = request()->user();

        if ($user->hasRole('PLATFORM_ADMIN')) {
            return;
        }

        abort_if(
            $shiftTemplate->tenant_id !== $user->tenant_id,
            403,
            'You do not have access to this shift template.'
        );
    }
}