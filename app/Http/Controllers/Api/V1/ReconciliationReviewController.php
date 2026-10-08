<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveShiftDiscrepancyRequest;
use App\Http\Requests\ReviewReconciliationRequest;
use App\Models\ReconciliationReport;
use App\Models\Shift;
use App\Models\ShiftDiscrepancy;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReconciliationReviewController extends Controller
{
    /*
     * Manager reviews the generated reconciliation.
     */
    public function review(
        ReviewReconciliationRequest $request,
        Shift $shift
    ): JsonResponse {
        $this->ensureManagerOwnsShift($shift);

        if (
            $shift->status !==
            'RECONCILIATION_PENDING'
        ) {
            return response()->json([
                'error' => [
                    'code' =>
                        'SHIFT_NOT_PENDING_RECONCILIATION',

                    'message' =>
                        'The shift is not awaiting reconciliation review.',

                    'details' => [
                        'shift_status' =>
                            $shift->status,
                    ],
                ],
            ], 409);
        }

        $report = ReconciliationReport::query()
            ->where(
                'shift_id',
                $shift->id
            )
            ->first();

        if (! $report) {
            return response()->json([
                'error' => [
                    'code' =>
                        'RECONCILIATION_REPORT_MISSING',

                    'message' =>
                        'No reconciliation report exists for this shift.',

                    'details' => [],
                ],
            ], 422);
        }

        /*
         * Explicit manager choice to flag
         * discrepancies.
         */
        if (
            $request->decision ===
            'FLAG_DISCREPANCY'
        ) {
            DB::transaction(
                function () use (
                    $request,
                    $shift,
                    $report
                ) {
                    $this->generateDiscrepancies(
                        $request,
                        $shift,
                        $report
                    );

                    $report->update([
                        'status' =>
                            'DISCREPANCY_OPEN',

                        'reviewed_at' =>
                            now(),

                        'reviewed_by_user_id' =>
                            $request->user()->id,

                        'review_notes' =>
                            $request->notes,
                    ]);
                }
            );

            return response()->json([
                'data' => [
                    'message' =>
                        'Reconciliation flagged for discrepancy review.',

                    'report_id' =>
                        $report->id,

                    'status' =>
                        'DISCREPANCY_OPEN',
                ],
            ]);
        }

        /*
         * Do not approve a report containing
         * non-zero variances accidentally.
         */
        $volumeVariance =
            abs(
                (float)
                $report->volume_variance_litres
            );

        $cashVariance =
            abs(
                (int)
                $report->cash_variance_minor
            );

        if (
            $volumeVariance > 0.001 ||
            $cashVariance > 0
        ) {
            return response()->json([
                'error' => [
                    'code' =>
                        'VARIANCE_REQUIRES_DISCREPANCY_REVIEW',

                    'message' =>
                        'The reconciliation contains a variance. Flag and resolve the discrepancy before approval.',

                    'details' => [
                        'volume_variance_litres' =>
                            $report
                                ->volume_variance_litres,

                        'cash_variance_minor' =>
                            $report
                                ->cash_variance_minor,
                    ],
                ],
            ], 409);
        }

        $report->update([
            'status' =>
                'APPROVED',

            'reviewed_at' =>
                now(),

            'reviewed_by_user_id' =>
                $request->user()->id,

            'review_notes' =>
                $request->notes,
        ]);

        return response()->json([
            'data' => [
                'message' =>
                    'Reconciliation approved.',

                'report_id' =>
                    $report->id,

                'status' =>
                    'APPROVED',
            ],
        ]);
    }

    public function resolveDiscrepancy(
        ResolveShiftDiscrepancyRequest $request,
        ShiftDiscrepancy $discrepancy
    ): JsonResponse {
        $shift =
            $discrepancy->shift;

        $this->ensureManagerOwnsShift(
            $shift
        );

        if (
            ! in_array(
                $discrepancy->status,
                ['OPEN', 'EXPLAINED'],
                true
            )
        ) {
            return response()->json([
                'error' => [
                    'code' =>
                        'DISCREPANCY_ALREADY_FINALIZED',

                    'message' =>
                        'This discrepancy can no longer be changed.',

                    'details' => [
                        'status' =>
                            $discrepancy->status,
                    ],
                ],
            ], 409);
        }

        $discrepancy->update([
            'status' =>
                $request->status,

            'resolution_notes' =>
                $request->resolution_notes,

            'resolved_by_user_id' =>
                $request->user()->id,

            'resolved_at' =>
                now(),
        ]);

        return response()->json([
            'data' =>
                $discrepancy->fresh(),
        ]);
    }

    public function approveAfterResolution(
        Shift $shift
    ): JsonResponse {
        $this->ensureManagerOwnsShift($shift);

        $report = ReconciliationReport::query()
            ->where(
                'shift_id',
                $shift->id
            )
            ->firstOrFail();

        if (
            $report->status !==
            'DISCREPANCY_OPEN'
        ) {
            return response()->json([
                'error' => [
                    'code' =>
                        'REPORT_NOT_IN_DISCREPANCY_REVIEW',

                    'message' =>
                        'This reconciliation is not awaiting discrepancy resolution.',

                    'details' => [],
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
                        'Resolve or explain all discrepancies before approval.',

                    'details' => [
                        'unresolved_count' =>
                            $unresolved,
                    ],
                ],
            ], 409);
        }

        $report->update([
            'status' =>
                'APPROVED',

            'reviewed_at' =>
                now(),

            'reviewed_by_user_id' =>
                request()->user()->id,
        ]);

        return response()->json([
            'data' => [
                'message' =>
                    'Reconciliation approved after discrepancy resolution.',

                'report_id' =>
                    $report->id,

                'status' =>
                    'APPROVED',
            ],
        ]);
    }

    private function generateDiscrepancies(
        ReviewReconciliationRequest $request,
        Shift $shift,
        ReconciliationReport $report
    ): void {
        /*
         * Create per-nozzle volume discrepancy
         * only where a variance exists.
         */
        $report->load('nozzleLines');

        foreach (
            $report->nozzleLines
            as $line
        ) {
            if (
                abs(
                    (float)
                    $line->variance_litres
                ) <= 0.001
            ) {
                continue;
            }

            ShiftDiscrepancy::firstOrCreate(
                [
                    'shift_id' =>
                        $shift->id,

                    'reconciliation_report_id' =>
                        $report->id,

                    'nozzle_id' =>
                        $line->nozzle_id,

                    'discrepancy_type' =>
                        'VOLUME_VARIANCE',
                ],
                [
                    'tenant_id' =>
                        $shift->tenant_id,

                    'station_id' =>
                        $shift->station_id,

                    'status' =>
                        'OPEN',

                    'variance_litres' =>
                        $line->variance_litres,

                    'description' =>
                        'Physical meter movement differs from recorded transaction litres.',

                    'created_by_user_id' =>
                        $request->user()->id,
                ]
            );
        }

        if (
            (int)
            $report->cash_variance_minor !== 0
        ) {
            ShiftDiscrepancy::firstOrCreate(
                [
                    'shift_id' =>
                        $shift->id,

                    'reconciliation_report_id' =>
                        $report->id,

                    'discrepancy_type' =>
                        'CASH_VARIANCE',

                    'nozzle_id' =>
                        null,
                ],
                [
                    'tenant_id' =>
                        $shift->tenant_id,

                    'station_id' =>
                        $shift->station_id,

                    'status' =>
                        'OPEN',

                    'variance_minor' =>
                        $report
                            ->cash_variance_minor,

                    'description' =>
                        'Declared physical cash differs from cash sales recorded in the shift ledger.',

                    'created_by_user_id' =>
                        $request->user()->id,
                ]
            );
        }
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