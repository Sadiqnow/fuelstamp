<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignShiftAttendantRequest;
use App\Http\Resources\ShiftAssignmentResource;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\StationStaff;
use Illuminate\Http\JsonResponse;

class ShiftAssignmentController extends Controller
{
    public function store(
        AssignShiftAttendantRequest $request,
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
                        'SHIFT_ASSIGNMENT_LOCKED',

                    'message' =>
                        'Attendants cannot be assigned at the current shift status.',

                    'details' => [
                        'shift_status' =>
                            $shift->status,
                    ],
                ],
            ], 409);
        }

        $validAttendant = StationStaff::query()
            ->where(
                'station_id',
                $shift->station_id
            )
            ->where(
                'user_id',
                $request->user_id
            )
            ->where(
                'staff_type',
                'ATTENDANT'
            )
            ->where(
                'status',
                'ACTIVE'
            )
            ->exists();

        if (! $validAttendant) {
            return response()->json([
                'error' => [
                    'code' =>
                        'INVALID_SHIFT_ATTENDANT',

                    'message' =>
                        'The selected user is not an active attendant for this station.',

                    'details' => [],
                ],
            ], 422);
        }

        $assignment =
            ShiftAssignment::firstOrCreate(
                [
                    'shift_id' =>
                        $shift->id,

                    'user_id' =>
                        $request->user_id,

                    'assignment_type' =>
                        'ATTENDANT',
                ],
                [
                    'tenant_id' =>
                        $shift->tenant_id,

                    'station_id' =>
                        $shift->station_id,

                    'status' =>
                        'ASSIGNED',

                    'assigned_at' =>
                        now(),
                ]
            );

        $assignment->load('user');

        return response()->json([
            'data' =>
                new ShiftAssignmentResource(
                    $assignment
                ),
        ], $assignment->wasRecentlyCreated ? 201 : 200);
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