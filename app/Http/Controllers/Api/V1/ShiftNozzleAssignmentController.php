<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignShiftNozzleRequest;
use App\Http\Resources\ShiftNozzleAssignmentResource;
use App\Models\Nozzle;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\ShiftNozzleAssignment;
use Illuminate\Http\JsonResponse;

class ShiftNozzleAssignmentController extends Controller
{
    public function store(
        AssignShiftNozzleRequest $request,
        Shift $shift
    ): JsonResponse {
        $this->ensureManagerOwnsShift($shift);

        if (! in_array(
            $shift->status,
            ['DRAFT', 'OPEN'],
            true
        )) {
            return response()->json([
                'error' => [
                    'code' =>
                        'NOZZLE_ASSIGNMENT_LOCKED',

                    'message' =>
                        'Nozzles cannot be assigned at the current shift status.',

                    'details' => [
                        'shift_status' =>
                            $shift->status,
                    ],
                ],
            ], 409);
        }

        $attendantAssigned =
            ShiftAssignment::query()
                ->where(
                    'shift_id',
                    $shift->id
                )
                ->where(
                    'user_id',
                    $request->attendant_user_id
                )
                ->where(
                    'assignment_type',
                    'ATTENDANT'
                )
                ->exists();

        if (! $attendantAssigned) {
            return response()->json([
                'error' => [
                    'code' =>
                        'ATTENDANT_NOT_ON_SHIFT',

                    'message' =>
                        'Assign this attendant to the shift before assigning a nozzle.',

                    'details' => [],
                ],
            ], 422);
        }

        $nozzle = Nozzle::query()
            ->where(
                'id',
                $request->nozzle_id
            )
            ->whereHas(
                'pump',
                fn ($query) =>
                    $query->where(
                        'station_id',
                        $shift->station_id
                    )
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->first();

        if (! $nozzle) {
            return response()->json([
                'error' => [
                    'code' =>
                        'INVALID_SHIFT_NOZZLE',

                    'message' =>
                        'The nozzle is not active or does not belong to this station.',

                    'details' => [],
                ],
            ], 422);
        }

        $alreadyAssigned =
            ShiftNozzleAssignment::query()
                ->where(
                    'shift_id',
                    $shift->id
                )
                ->where(
                    'nozzle_id',
                    $nozzle->id
                )
                ->first();

        if ($alreadyAssigned) {
            return response()->json([
                'error' => [
                    'code' =>
                        'NOZZLE_ALREADY_ASSIGNED',

                    'message' =>
                        'This nozzle has already been assigned within the shift.',

                    'details' => [],
                ],
            ], 409);
        }

        $assignment =
            ShiftNozzleAssignment::create([
                'tenant_id' =>
                    $shift->tenant_id,

                'station_id' =>
                    $shift->station_id,

                'shift_id' =>
                    $shift->id,

                'nozzle_id' =>
                    $nozzle->id,

                'attendant_user_id' =>
                    $request->attendant_user_id,

                'custody_status' =>
                    'PENDING',

                'assigned_at' =>
                    now(),
            ]);

        $assignment->load([
            'nozzle',
            'attendant',
        ]);

        return response()->json([
            'data' =>
                new ShiftNozzleAssignmentResource(
                    $assignment
                ),
        ], 201);
    }

    private function ensureManagerOwnsShift(
        Shift $shift
    ): void {
        $user = request()->user();

        abort_if(
            $shift->manager_user_id !== $user->id,
            403,
            'You are not the manager responsible for this shift.'
        );
    }
}