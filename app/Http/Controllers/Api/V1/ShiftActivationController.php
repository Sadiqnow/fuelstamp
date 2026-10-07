<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ShiftResource;
use App\Models\MeterReading;
use App\Models\Shift;
use App\Models\ShiftNozzleAssignment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ShiftActivationController extends Controller
{
    public function activate(
        Shift $shift
    ): JsonResponse {
        $this->ensureManagerOwnsShift($shift);

        if ($shift->status !== 'OPEN') {
            return response()->json([
                'error' => [
                    'code' =>
                        'SHIFT_NOT_READY_FOR_ACTIVATION',

                    'message' =>
                        'Only an OPEN shift can be activated.',

                    'details' => [
                        'shift_status' =>
                            $shift->status,
                    ],
                ],
            ], 409);
        }

        /*
         * Production rule:
         * only approved stations may trade.
         */
        if ($shift->station->status !== 'ACTIVE') {
            return response()->json([
                'error' => [
                    'code' =>
                        'STATION_NOT_ACTIVE',

                    'message' =>
                        'The station must be ACTIVE before a shift can be activated.',

                    'details' => [
                        'station_status' =>
                            $shift->station->status,
                    ],
                ],
            ], 409);
        }

        $nozzleAssignments =
            ShiftNozzleAssignment::query()
                ->where(
                    'shift_id',
                    $shift->id
                )
                ->get();

        if ($nozzleAssignments->isEmpty()) {
            return response()->json([
                'error' => [
                    'code' =>
                        'NO_NOZZLES_ASSIGNED',

                    'message' =>
                        'At least one nozzle must be assigned before activation.',

                    'details' => [],
                ],
            ], 422);
        }

        $pendingCustody =
            $nozzleAssignments
                ->where(
                    'custody_status',
                    '!=',
                    'ACCEPTED'
                );

        if ($pendingCustody->isNotEmpty()) {
            return response()->json([
                'error' => [
                    'code' =>
                        'CUSTODY_NOT_ACCEPTED',

                    'message' =>
                        'All assigned nozzles must have accepted custody before activation.',

                    'details' => [
                        'pending_count' =>
                            $pendingCustody->count(),
                    ],
                ],
            ], 422);
        }

        $assignedNozzleIds =
            $nozzleAssignments
                ->pluck('nozzle_id');

        $openingReadingCount =
            MeterReading::query()
                ->where(
                    'shift_id',
                    $shift->id
                )
                ->where(
                    'reading_type',
                    'OPENING'
                )
                ->whereIn(
                    'nozzle_id',
                    $assignedNozzleIds
                )
                ->count();

        if (
            $openingReadingCount !==
            $assignedNozzleIds->count()
        ) {
            return response()->json([
                'error' => [
                    'code' =>
                        'OPENING_READINGS_INCOMPLETE',

                    'message' =>
                        'Every assigned nozzle must have an opening meter reading before activation.',

                    'details' => [
                        'assigned_nozzles' =>
                            $assignedNozzleIds->count(),

                        'opening_readings' =>
                            $openingReadingCount,
                    ],
                ],
            ], 422);
        }

        DB::transaction(
            function () use ($shift) {
                $shift->update([
                    'status' => 'ACTIVE',
                    'activated_at' => now(),
                ]);
            }
        );

        $shift->load([
            'shiftTemplate',
            'manager',
            'assignments.user',
            'nozzleAssignments.nozzle',
            'nozzleAssignments.attendant',
            'meterReadings',
        ]);

        return response()->json([
            'data' =>
                new ShiftResource($shift),
        ]);
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