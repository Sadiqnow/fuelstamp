<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOpeningMeterReadingRequest;
use App\Http\Resources\MeterReadingResource;
use App\Models\MeterReading;
use App\Models\Shift;
use App\Models\ShiftNozzleAssignment;
use Illuminate\Http\JsonResponse;

class MeterReadingController extends Controller
{
    public function storeOpening(
        StoreOpeningMeterReadingRequest $request,
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
                        'OPENING_READING_LOCKED',

                    'message' =>
                        'Opening readings cannot be recorded at the current shift status.',

                    'details' => [
                        'shift_status' =>
                            $shift->status,
                    ],
                ],
            ], 409);
        }

        $assignedNozzle =
            ShiftNozzleAssignment::query()
                ->where(
                    'shift_id',
                    $shift->id
                )
                ->where(
                    'nozzle_id',
                    $request->nozzle_id
                )
                ->exists();

        if (! $assignedNozzle) {
            return response()->json([
                'error' => [
                    'code' =>
                        'NOZZLE_NOT_ASSIGNED_TO_SHIFT',

                    'message' =>
                        'The selected nozzle is not assigned to this shift.',

                    'details' => [],
                ],
            ], 422);
        }

        $existing = MeterReading::query()
            ->where(
                'shift_id',
                $shift->id
            )
            ->where(
                'nozzle_id',
                $request->nozzle_id
            )
            ->where(
                'reading_type',
                'OPENING'
            )
            ->first();

        if ($existing) {
            return response()->json([
                'error' => [
                    'code' =>
                        'OPENING_READING_EXISTS',

                    'message' =>
                        'An opening reading already exists for this nozzle in this shift.',

                    'details' => [
                        'meter_reading_id' =>
                            $existing->id,
                    ],
                ],
            ], 409);
        }

        $reading = MeterReading::create([
            'tenant_id' =>
                $shift->tenant_id,

            'station_id' =>
                $shift->station_id,

            'shift_id' =>
                $shift->id,

            'nozzle_id' =>
                $request->nozzle_id,

            'captured_by_user_id' =>
                $request->user()->id,

            'reading_type' =>
                'OPENING',

            'meter_litres' =>
                $request->meter_litres,

            'captured_at' =>
                now(),

            'evidence_path' =>
                $request->evidence_path,

            'notes' =>
                $request->notes,
        ]);

        /*
         * Once the first opening reading is captured,
         * move DRAFT → OPEN.
         */
        if ($shift->status === 'DRAFT') {
            $shift->update([
                'status' => 'OPEN',
                'opened_at' => now(),
            ]);
        }

        return response()->json([
            'data' =>
                new MeterReadingResource($reading),
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