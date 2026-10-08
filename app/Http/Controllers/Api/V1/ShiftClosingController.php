<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCashDeclarationRequest;
use App\Http\Requests\StoreClosingMeterReadingRequest;
use App\Http\Resources\MeterReadingResource;
use App\Models\MeterReading;
use App\Models\Shift;
use App\Models\ShiftCashDeclaration;
use App\Models\ShiftNozzleAssignment;
use Illuminate\Http\JsonResponse;

class ShiftClosingController extends Controller
{
    public function storeClosingReading(
        StoreClosingMeterReadingRequest $request,
        Shift $shift
    ): JsonResponse {
        $this->ensureManagerOwnsShift($shift);

        if (! in_array(
            $shift->status,
            ['ACTIVE', 'CLOSING'],
            true
        )) {
            return response()->json([
                'error' => [
                    'code' => 'SHIFT_NOT_CLOSABLE',
                    'message' =>
                        'Closing readings require an ACTIVE or CLOSING shift.',
                    'details' => [
                        'shift_status' => $shift->status,
                    ],
                ],
            ], 409);
        }

        $assigned = ShiftNozzleAssignment::query()
            ->where('shift_id', $shift->id)
            ->where('nozzle_id', $request->nozzle_id)
            ->exists();

        if (! $assigned) {
            return response()->json([
                'error' => [
                    'code' => 'NOZZLE_NOT_ON_SHIFT',
                    'message' =>
                        'This nozzle is not assigned to the shift.',
                    'details' => [],
                ],
            ], 422);
        }

        $opening = MeterReading::query()
            ->where('shift_id', $shift->id)
            ->where('nozzle_id', $request->nozzle_id)
            ->where('reading_type', 'OPENING')
            ->first();

        if (! $opening) {
            return response()->json([
                'error' => [
                    'code' => 'OPENING_READING_MISSING',
                    'message' =>
                        'An opening reading is required before closing.',
                    'details' => [],
                ],
            ], 422);
        }

        if (
            (float) $request->meter_litres <
            (float) $opening->meter_litres
        ) {
            return response()->json([
                'error' => [
                    'code' => 'INVALID_CLOSING_METER',
                    'message' =>
                        'Closing meter cannot be less than opening meter.',
                    'details' => [
                        'opening_meter_litres' =>
                            $opening->meter_litres,
                    ],
                ],
            ], 422);
        }

        $existing = MeterReading::query()
            ->where('shift_id', $shift->id)
            ->where('nozzle_id', $request->nozzle_id)
            ->where('reading_type', 'CLOSING')
            ->first();

        if ($existing) {
            return response()->json([
                'error' => [
                    'code' => 'CLOSING_READING_EXISTS',
                    'message' =>
                        'A closing reading already exists.',
                    'details' => [
                        'meter_reading_id' =>
                            $existing->id,
                    ],
                ],
            ], 409);
        }

        $reading = MeterReading::create([
            'tenant_id' => $shift->tenant_id,
            'station_id' => $shift->station_id,
            'shift_id' => $shift->id,
            'nozzle_id' => $request->nozzle_id,

            'captured_by_user_id' =>
                $request->user()->id,

            'reading_type' => 'CLOSING',

            'meter_litres' =>
                $request->meter_litres,

            'captured_at' => now(),

            'notes' => $request->notes,
        ]);

        if ($shift->status === 'ACTIVE') {
            $shift->update([
                'status' => 'CLOSING',
                'closing_started_at' => now(),
            ]);
        }

        return response()->json([
            'data' =>
                new MeterReadingResource($reading),
        ], 201);
    }

    public function declareCash(
        StoreCashDeclarationRequest $request,
        Shift $shift
    ): JsonResponse {
        $user = $request->user();

        $custody = ShiftNozzleAssignment::query()
            ->where('shift_id', $shift->id)
            ->where(
                'attendant_user_id',
                $user->id
            )
            ->where(
                'custody_status',
                'ACCEPTED'
            )
            ->exists();

        abort_unless(
            $custody,
            403,
            'You are not an accepted attendant on this shift.'
        );

        $declaration =
            ShiftCashDeclaration::updateOrCreate(
                [
                    'shift_id' => $shift->id,
                    'declared_by_user_id' =>
                        $user->id,
                ],
                [
                    'tenant_id' =>
                        $shift->tenant_id,

                    'station_id' =>
                        $shift->station_id,

                    'declared_cash_minor' =>
                        $request->declared_cash_minor,

                    'declared_at' =>
                        now(),

                    'notes' =>
                        $request->notes,
                ]
            );

        return response()->json([
            'data' => $declaration,
        ], $declaration->wasRecentlyCreated ? 201 : 200);
    }

    private function ensureManagerOwnsShift(
        Shift $shift
    ): void {
        abort_if(
            $shift->manager_user_id !==
                request()->user()->id,
            403,
            'You are not the manager responsible for this shift.'
        );
    }
}