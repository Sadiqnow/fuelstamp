<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MeterReading;
use App\Models\ReconciliationNozzleLine;
use App\Models\ReconciliationReport;
use App\Models\Shift;
use App\Models\ShiftCashDeclaration;
use App\Models\ShiftNozzleAssignment;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReconciliationController extends Controller
{
    public function generate(
        Shift $shift
    ): JsonResponse {
        $this->ensureManagerOwnsShift($shift);

        if ($shift->status !== 'CLOSING') {
            return response()->json([
                'error' => [
                    'code' =>
                        'SHIFT_NOT_IN_CLOSING',

                    'message' =>
                        'Reconciliation requires a CLOSING shift.',

                    'details' => [
                        'shift_status' =>
                            $shift->status,
                    ],
                ],
            ], 409);
        }

        $nozzleAssignments =
            ShiftNozzleAssignment::query()
                ->where('shift_id', $shift->id)
                ->get();

        foreach ($nozzleAssignments as $assignment) {
            $hasOpening = MeterReading::query()
                ->where('shift_id', $shift->id)
                ->where(
                    'nozzle_id',
                    $assignment->nozzle_id
                )
                ->where(
                    'reading_type',
                    'OPENING'
                )
                ->exists();

            $hasClosing = MeterReading::query()
                ->where('shift_id', $shift->id)
                ->where(
                    'nozzle_id',
                    $assignment->nozzle_id
                )
                ->where(
                    'reading_type',
                    'CLOSING'
                )
                ->exists();

            if (! $hasOpening || ! $hasClosing) {
                return response()->json([
                    'error' => [
                        'code' =>
                            'METER_READINGS_INCOMPLETE',

                        'message' =>
                            'Every assigned nozzle needs opening and closing readings.',

                        'details' => [
                            'nozzle_id' =>
                                $assignment->nozzle_id,
                        ],
                    ],
                ], 422);
            }
        }

        $report = DB::transaction(
            function () use (
                $shift,
                $nozzleAssignments
            ) {
                $existing =
                    ReconciliationReport::where(
                        'shift_id',
                        $shift->id
                    )->first();

                if ($existing) {
                    return $existing;
                }

                $ledgerLitres =
                    Transaction::query()
                        ->where(
                            'shift_id',
                            $shift->id
                        )
                        ->where(
                            'status',
                            'COMPLETED'
                        )
                        ->sum(
                            'volume_litres'
                        );

                $totalRevenue =
                    Transaction::query()
                        ->where(
                            'shift_id',
                            $shift->id
                        )
                        ->where(
                            'status',
                            'COMPLETED'
                        )
                        ->sum(
                            'amount_minor'
                        );

                $expectedCash =
                    DB::table(
                        'transaction_payments'
                    )
                        ->join(
                            'transactions',
                            'transactions.id',
                            '=',
                            'transaction_payments.transaction_id'
                        )
                        ->where(
                            'transactions.shift_id',
                            $shift->id
                        )
                        ->where(
                            'transactions.status',
                            'COMPLETED'
                        )
                        ->where(
                            'transaction_payments.payment_method',
                            'CASH'
                        )
                        ->where(
                            'transaction_payments.status',
                            'CONFIRMED'
                        )
                        ->sum(
                            'transaction_payments.amount_minor'
                        );

                $declaredCash =
                    ShiftCashDeclaration::query()
                        ->where(
                            'shift_id',
                            $shift->id
                        )
                        ->sum(
                            'declared_cash_minor'
                        );

                $meterMovementTotal = 0;

                $report =
                    ReconciliationReport::create([
                        'tenant_id' =>
                            $shift->tenant_id,

                        'station_id' =>
                            $shift->station_id,

                        'shift_id' =>
                            $shift->id,

                        'generated_by_user_id' =>
                            request()->user()->id,

                        'status' =>
                            'PENDING_REVIEW',

                        'ledger_litres' =>
                            $ledgerLitres,

                        'total_revenue_minor' =>
                            $totalRevenue,

                        'expected_cash_minor' =>
                            $expectedCash,

                        'declared_cash_minor' =>
                            $declaredCash,

                        'cash_variance_minor' =>
                            $declaredCash -
                            $expectedCash,

                        'generated_at' =>
                            now(),
                    ]);

                foreach (
                    $nozzleAssignments
                    as $assignment
                ) {
                    $opening =
                        MeterReading::query()
                            ->where(
                                'shift_id',
                                $shift->id
                            )
                            ->where(
                                'nozzle_id',
                                $assignment->nozzle_id
                            )
                            ->where(
                                'reading_type',
                                'OPENING'
                            )
                            ->firstOrFail();

                    $closing =
                        MeterReading::query()
                            ->where(
                                'shift_id',
                                $shift->id
                            )
                            ->where(
                                'nozzle_id',
                                $assignment->nozzle_id
                            )
                            ->where(
                                'reading_type',
                                'CLOSING'
                            )
                            ->firstOrFail();

                    $movement =
                        (float) $closing->meter_litres -
                        (float) $opening->meter_litres;

                    $nozzleLedger =
                        Transaction::query()
                            ->where(
                                'shift_id',
                                $shift->id
                            )
                            ->where(
                                'nozzle_id',
                                $assignment->nozzle_id
                            )
                            ->where(
                                'status',
                                'COMPLETED'
                            )
                            ->sum(
                                'volume_litres'
                            );

                    $variance =
                        $movement -
                        (float) $nozzleLedger;

                    $meterMovementTotal +=
                        $movement;

                    ReconciliationNozzleLine::create([
                        'reconciliation_report_id' =>
                            $report->id,

                        'nozzle_id' =>
                            $assignment->nozzle_id,

                        'opening_meter_litres' =>
                            $opening->meter_litres,

                        'closing_meter_litres' =>
                            $closing->meter_litres,

                        'meter_movement_litres' =>
                            $movement,

                        'ledger_litres' =>
                            $nozzleLedger,

                        'variance_litres' =>
                            $variance,
                    ]);
                }

                $report->update([
                    'meter_movement_litres' =>
                        $meterMovementTotal,

                    'volume_variance_litres' =>
                        $meterMovementTotal -
                        (float) $ledgerLitres,
                ]);

                $shift->update([
                    'status' =>
                        'RECONCILIATION_PENDING',
                ]);

                return $report->fresh();
            }
        );

        $report->load(
            'nozzleLines.nozzle'
        );

        return response()->json([
            'data' => $report,
        ]);
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