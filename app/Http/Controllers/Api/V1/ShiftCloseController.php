<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ReconciliationReport;
use App\Models\Shift;
use App\Models\ShiftAuditPackage;
use App\Models\ShiftDiscrepancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ShiftCloseController extends Controller
{
    public function close(
        Shift $shift
    ): JsonResponse {
        $this->ensureManagerOwnsShift(
            $shift
        );

        if (
            $shift->status !==
            'RECONCILIATION_PENDING'
        ) {
            return response()->json([
                'error' => [
                    'code' =>
                        'SHIFT_NOT_READY_TO_CLOSE',

                    'message' =>
                        'The shift must be awaiting reconciliation closure.',

                    'details' => [
                        'shift_status' =>
                            $shift->status,
                    ],
                ],
            ], 409);
        }

        $report =
            ReconciliationReport::query()
                ->where(
                    'shift_id',
                    $shift->id
                )
                ->first();

        if (
            ! $report ||
            $report->status !== 'APPROVED'
        ) {
            return response()->json([
                'error' => [
                    'code' =>
                        'RECONCILIATION_NOT_APPROVED',

                    'message' =>
                        'The reconciliation must be approved before the shift can close.',

                    'details' => [
                        'report_status' =>
                            $report?->status,
                    ],
                ],
            ], 409);
        }

        $unresolved =
            ShiftDiscrepancy::query()
                ->where(
                    'shift_id',
                    $shift->id
                )
                ->whereNotIn(
                    'status',
                    [
                        'RESOLVED',
                        'EXPLAINED',
                    ]
                )
                ->count();

        if ($unresolved > 0) {
            return response()->json([
                'error' => [
                    'code' =>
                        'UNRESOLVED_DISCREPANCIES',

                    'message' =>
                        'The shift has unresolved discrepancies.',

                    'details' => [
                        'unresolved_count' =>
                            $unresolved,
                    ],
                ],
            ], 409);
        }

        $auditPackage = DB::transaction(
            function () use (
                $shift,
                $report
            ) {
                /*
                 * Prevent duplicate sealing.
                 */
                $existing =
                    ShiftAuditPackage::where(
                        'shift_id',
                        $shift->id
                    )->first();

                if ($existing) {
                    return $existing;
                }

                $shift->load([
                    'station',
                    'shiftTemplate',
                    'manager',
                    'assignments.user',
                    'nozzleAssignments.nozzle',
                    'nozzleAssignments.attendant',
                    'meterReadings',
                    'transactions.attendant',
                    'transactions.nozzle',
                    'transactions.fuelProduct',
                    'transactions.manualDetail',
                    'transactions.payment',
                    'cashDeclarations',
                    'discrepancies',
                ]);

                $report->load([
                    'nozzleLines.nozzle',
                ]);

                /*
                 * Canonical audit snapshot.
                 */
                $payload = [
                    'schema_version' =>
                        '1.0',

                    'shift' => [
                        'id' =>
                            $shift->id,

                        'shift_code' =>
                            $shift->shift_code,

                        'tenant_id' =>
                            $shift->tenant_id,

                        'station_id' =>
                            $shift->station_id,

                        'manager_user_id' =>
                            $shift->manager_user_id,

                        'opened_at' =>
                            $shift->opened_at?->toISOString(),

                        'activated_at' =>
                            $shift->activated_at?->toISOString(),

                        'closing_started_at' =>
                            $shift
                                ->closing_started_at
                                ?->toISOString(),
                    ],

                    'meter_readings' =>
                        $shift
                            ->meterReadings
                            ->map(
                                fn ($reading) => [
                                    'id' =>
                                        $reading->id,

                                    'nozzle_id' =>
                                        $reading
                                            ->nozzle_id,

                                    'reading_type' =>
                                        $reading
                                            ->reading_type,

                                    'meter_litres' =>
                                        $reading
                                            ->meter_litres,

                                    'captured_by_user_id' =>
                                        $reading
                                            ->captured_by_user_id,

                                    'captured_at' =>
                                        $reading
                                            ->captured_at
                                            ?->toISOString(),
                                ]
                            )
                            ->values()
                            ->all(),

                    'transactions' =>
                        $shift
                            ->transactions
                            ->map(
                                fn ($tx) => [
                                    'id' =>
                                        $tx->id,

                                    'transaction_code' =>
                                        $tx
                                            ->transaction_code,

                                    'lane_type' =>
                                        $tx->lane_type,

                                    'attendant_user_id' =>
                                        $tx
                                            ->attendant_user_id,

                                    'nozzle_id' =>
                                        $tx->nozzle_id,

                                    'fuel_product_id' =>
                                        $tx
                                            ->fuel_product_id,

                                    'volume_litres' =>
                                        $tx
                                            ->volume_litres,

                                    'unit_price_minor' =>
                                        $tx
                                            ->unit_price_minor,

                                    'amount_minor' =>
                                        $tx
                                            ->amount_minor,

                                    'status' =>
                                        $tx->status,

                                    'completed_at' =>
                                        $tx
                                            ->completed_at
                                            ?->toISOString(),

                                    'payment' =>
                                        $tx->payment
                                        ? [
                                            'method' =>
                                                $tx
                                                    ->payment
                                                    ->payment_method,

                                            'amount_minor' =>
                                                $tx
                                                    ->payment
                                                    ->amount_minor,

                                            'reference' =>
                                                $tx
                                                    ->payment
                                                    ->payment_reference,
                                        ]
                                        : null,
                                ]
                            )
                            ->values()
                            ->all(),

                    'cash_declarations' =>
                        $shift
                            ->cashDeclarations
                            ->map(
                                fn ($row) => [
                                    'declared_by_user_id' =>
                                        $row
                                            ->declared_by_user_id,

                                    'declared_cash_minor' =>
                                        $row
                                            ->declared_cash_minor,

                                    'declared_at' =>
                                        $row
                                            ->declared_at
                                            ?->toISOString(),
                                ]
                            )
                            ->values()
                            ->all(),

                    'reconciliation' => [
                        'id' =>
                            $report->id,

                        'status' =>
                            $report->status,

                        'ledger_litres' =>
                            $report
                                ->ledger_litres,

                        'meter_movement_litres' =>
                            $report
                                ->meter_movement_litres,

                        'volume_variance_litres' =>
                            $report
                                ->volume_variance_litres,

                        'total_revenue_minor' =>
                            $report
                                ->total_revenue_minor,

                        'expected_cash_minor' =>
                            $report
                                ->expected_cash_minor,

                        'declared_cash_minor' =>
                            $report
                                ->declared_cash_minor,

                        'cash_variance_minor' =>
                            $report
                                ->cash_variance_minor,

                        'reviewed_by_user_id' =>
                            $report
                                ->reviewed_by_user_id,

                        'reviewed_at' =>
                            $report
                                ->reviewed_at
                                ?->toISOString(),
                    ],

                    'discrepancies' =>
                        $shift
                            ->discrepancies
                            ->map(
                                fn ($row) => [
                                    'id' =>
                                        $row->id,

                                    'type' =>
                                        $row
                                            ->discrepancy_type,

                                    'status' =>
                                        $row->status,

                                    'variance_litres' =>
                                        $row
                                            ->variance_litres,

                                    'variance_minor' =>
                                        $row
                                            ->variance_minor,

                                    'resolution_notes' =>
                                        $row
                                            ->resolution_notes,
                                ]
                            )
                            ->values()
                            ->all(),

                    'sealed_at' =>
                        now()->toISOString(),
                ];

                /*
                 * Sort top-level keys before hashing.
                 * The JSON itself is persisted with the hash.
                 */
                ksort($payload);

                $canonicalJson = json_encode(
                    $payload,
                    JSON_UNESCAPED_SLASHES |
                    JSON_UNESCAPED_UNICODE
                );

                $hash = hash(
                    'sha256',
                    $canonicalJson
                );

                $package =
                    ShiftAuditPackage::create([
                        'tenant_id' =>
                            $shift->tenant_id,

                        'station_id' =>
                            $shift->station_id,

                        'shift_id' =>
                            $shift->id,

                        'reconciliation_report_id' =>
                            $report->id,

                        'package_hash' =>
                            $hash,

                        'package_payload' =>
                            $payload,

                        'sealed_by_user_id' =>
                            request()->user()->id,

                        'sealed_at' =>
                            now(),
                    ]);

                /*
                 * Release accepted custody.
                 */
                $shift
                    ->nozzleAssignments()
                    ->where(
                        'custody_status',
                        'ACCEPTED'
                    )
                    ->update([
                        'custody_status' =>
                            'RELEASED',

                        'released_at' =>
                            now(),
                    ]);

                $shift
                    ->assignments()
                    ->where(
                        'status',
                        'ACCEPTED'
                    )
                    ->update([
                        'status' =>
                            'RELEASED',

                        'released_at' =>
                            now(),
                    ]);

                $shift->update([
                    'status' =>
                        'SEALED',

                    'closed_at' =>
                        now(),
                ]);

                return $package;
            }
        );

        return response()->json([
            'data' => [
                'shift_id' =>
                    $shift->id,

                'shift_status' =>
                    'SEALED',

                'audit_package_id' =>
                    $auditPackage->id,

                'package_hash' =>
                    $auditPackage->package_hash,

                'sealed_at' =>
                    $auditPackage
                        ->sealed_at
                        ?->toISOString(),
            ],
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