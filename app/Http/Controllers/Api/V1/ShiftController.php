<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShiftRequest;
use App\Http\Resources\ShiftResource;
use App\Models\Shift;
use App\Models\ShiftTemplate;
use App\Models\Station;
use App\Models\StationStaff;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ShiftController extends Controller
{
    public function index(
        Station $station
    ): AnonymousResourceCollection {
        $this->ensureManagerScope($station);

        $shifts = Shift::query()
            ->where('station_id', $station->id)
            ->with([
                'shiftTemplate',
                'manager',
                'assignments.user',
                'nozzleAssignments.nozzle',
                'nozzleAssignments.attendant',
            ])
            ->latest()
            ->get();

        return ShiftResource::collection($shifts);
    }

    public function store(
        StoreShiftRequest $request,
        Station $station
    ): JsonResponse {
        $this->ensureManagerScope($station);

        $template = ShiftTemplate::query()
            ->where('id', $request->shift_template_id)
            ->where('station_id', $station->id)
            ->where('active', true)
            ->first();

        if (! $template) {
            return response()->json([
                'error' => [
                    'code' =>
                        'INVALID_SHIFT_TEMPLATE',

                    'message' =>
                        'The selected shift template does not belong to this station or is inactive.',

                    'details' => [],
                ],
            ], 422);
        }

        $manager = $request->user();

        $shift = Shift::create([
            'tenant_id' =>
                $station->tenant_id,

            'station_id' =>
                $station->id,

            'shift_template_id' =>
                $template->id,

            'manager_user_id' =>
                $manager->id,

            'shift_code' =>
                $this->generateShiftCode($station),

            'status' =>
                'DRAFT',

            'scheduled_start_at' =>
                $request->scheduled_start_at,

            'notes' =>
                $request->notes,
        ]);

        $shift->load([
            'shiftTemplate',
            'manager',
            'assignments.user',
            'nozzleAssignments.nozzle',
            'nozzleAssignments.attendant',
        ]);

        return response()->json([
            'data' =>
                new ShiftResource($shift),
        ], 201);
    }

    public function show(
        Shift $shift
    ): ShiftResource {
        $this->ensureShiftScope($shift);

        $shift->load([
            'shiftTemplate',
            'manager',
            'assignments.user',
            'nozzleAssignments.nozzle',
            'nozzleAssignments.attendant',
            'meterReadings',
        ]);

        return new ShiftResource($shift);
    }

    private function ensureManagerScope(
        Station $station
    ): void {
        $user = request()->user();

        abort_if(
            $user->tenant_id !== $station->tenant_id,
            403,
            'You do not have access to this station.'
        );

        $assigned = StationStaff::query()
            ->where('station_id', $station->id)
            ->where('user_id', $user->id)
            ->where(
                'staff_type',
                'STATION_MANAGER'
            )
            ->where('status', 'ACTIVE')
            ->exists();

        abort_unless(
            $assigned,
            403,
            'You are not an active manager for this station.'
        );
    }

    private function ensureShiftScope(
        Shift $shift
    ): void {
        $this->ensureManagerScope(
            $shift->station
        );
    }

    private function generateShiftCode(
        Station $station
    ): string {
        $prefix = now()->format(
            'Ymd-His'
        );

        $sequence = Shift::query()
            ->where('station_id', $station->id)
            ->whereDate('created_at', today())
            ->count() + 1;

        return sprintf(
            'SH-%s-%03d',
            $prefix,
            $sequence
        );
    }
}