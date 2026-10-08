<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\DailyStationReportRequest;
use App\Models\ReconciliationReport;
use App\Models\Shift;
use App\Models\ShiftDiscrepancy;
use App\Models\Station;
use App\Models\StationStaff;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DailyStationReportController extends Controller
{
    public function show(
        DailyStationReportRequest $request,
        Station $station
    ): JsonResponse {
        $user = $request->user();

        /*
         * Tenant boundary.
         */
        abort_if(
            $station->tenant_id !== $user->tenant_id,
            403,
            'You do not have access to this station.'
        );

        /*
         * Manager must actually belong to this station.
         * Platform Admin may inspect tenant station reports.
         */
        $isAdmin = $user->hasRole('PLATFORM_ADMIN');

        if (! $isAdmin) {
            $isManager = $user->hasRole('STATION_MANAGER');

            abort_unless(
                $isManager,
                403,
                'You are not authorized to view this report.'
            );

            $activeStationStaff =
                StationStaff::query()
                    ->where('station_id', $station->id)
                    ->where('user_id', $user->id)
                    ->where('staff_type', 'STATION_MANAGER')
                    ->where('status', 'ACTIVE')
                    ->exists();

            abort_unless(
                $activeStationStaff,
                403,
                'You are not an active manager at this station.'
            );
        }

        $reportDate =
            Carbon::createFromFormat(
                'Y-m-d',
                $request->date
            );

        $dayStart =
            $reportDate
                ->copy()
                ->startOfDay();

        $dayEnd =
            $reportDate
                ->copy()
                ->endOfDay();

        /*
         * Canonical completed transactions for the day.
         */
        $transactionBase =
            Transaction::query()
                ->where(
                    'station_id',
                    $station->id
                )
                ->where(
                    'tenant_id',
                    $station->tenant_id
                )
                ->where(
                    'status',
                    'COMPLETED'
                )
                ->whereBetween(
                    'completed_at',
                    [
                        $dayStart,
                        $dayEnd,
                    ]
                );

        $transactionCount =
            (clone $transactionBase)->count();

        $totalLitres =
            (clone $transactionBase)
                ->sum('volume_litres');

        $totalRevenueMinor =
            (clone $transactionBase)
                ->sum('amount_minor');

        /*
         * Payment totals.
         */
        $paymentTotals =
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
                    'transactions.station_id',
                    $station->id
                )
                ->where(
                    'transactions.tenant_id',
                    $station->tenant_id
                )
                ->where(
                    'transactions.status',
                    'COMPLETED'
                )
                ->where(
                    'transaction_payments.status',
                    'CONFIRMED'
                )
                ->whereBetween(
                    'transactions.completed_at',
                    [
                        $dayStart,
                        $dayEnd,
                    ]
                )
                ->select(
                    'transaction_payments.payment_method',
                    DB::raw(
                        'SUM(transaction_payments.amount_minor) as total_minor'
                    ),
                    DB::raw(
                        'COUNT(*) as transaction_count'
                    )
                )
                ->groupBy(
                    'transaction_payments.payment_method'
                )
                ->get()
                ->keyBy('payment_method');

        $paymentSummary =
            collect([
                'CASH',
                'POS',
                'TRANSFER',
                'OTHER',
            ])
                ->map(
                    function ($method) use (
                        $paymentTotals
                    ) {
                        $row =
                            $paymentTotals
                                ->get($method);

                        $minor =
                            (int) (
                                $row?->total_minor ?? 0
                            );

                        return [
                            'payment_method' =>
                                $method,

                            'transaction_count' =>
                                (int) (
                                    $row?->transaction_count ?? 0
                                ),

                            'amount_minor' =>
                                $minor,

                            'amount_naira' =>
                                number_format(
                                    $minor / 100,
                                    2,
                                    '.',
                                    ''
                                ),
                        ];
                    }
                )
                ->values();

        /*
         * Sales by fuel product.
         */
        $salesByProduct =
            Transaction::query()
                ->where(
                    'transactions.station_id',
                    $station->id
                )
                ->where(
                    'transactions.tenant_id',
                    $station->tenant_id
                )
                ->where(
                    'transactions.status',
                    'COMPLETED'
                )
                ->whereBetween(
                    'transactions.completed_at',
                    [
                        $dayStart,
                        $dayEnd,
                    ]
                )
                ->join(
                    'fuel_products',
                    'fuel_products.id',
                    '=',
                    'transactions.fuel_product_id'
                )
                ->select(
                    'transactions.fuel_product_id',
                    'fuel_products.code',
                    'fuel_products.name',
                    DB::raw(
                        'COUNT(transactions.id) as transaction_count'
                    ),
                    DB::raw(
                        'SUM(transactions.volume_litres) as total_litres'
                    ),
                    DB::raw(
                        'SUM(transactions.amount_minor) as total_minor'
                    )
                )
                ->groupBy(
                    'transactions.fuel_product_id',
                    'fuel_products.code',
                    'fuel_products.name'
                )
                ->get()
                ->map(
                    fn ($row) => [
                        'fuel_product_id' =>
                            $row->fuel_product_id,

                        'code' =>
                            $row->code,

                        'name' =>
                            $row->name,

                        'transaction_count' =>
                            (int) $row->transaction_count,

                        'total_litres' =>
                            number_format(
                                (float) $row->total_litres,
                                3,
                                '.',
                                ''
                            ),

                        'total_minor' =>
                            (int) $row->total_minor,

                        'total_naira' =>
                            number_format(
                                $row->total_minor / 100,
                                2,
                                '.',
                                ''
                            ),
                    ]
                )
                ->values();

        /*
         * Sales by nozzle.
         */
        $salesByNozzle =
            Transaction::query()
                ->where(
                    'transactions.station_id',
                    $station->id
                )
                ->where(
                    'transactions.tenant_id',
                    $station->tenant_id
                )
                ->where(
                    'transactions.status',
                    'COMPLETED'
                )
                ->whereBetween(
                    'transactions.completed_at',
                    [
                        $dayStart,
                        $dayEnd,
                    ]
                )
                ->join(
                    'nozzles',
                    'nozzles.id',
                    '=',
                    'transactions.nozzle_id'
                )
                ->select(
                    'transactions.nozzle_id',
                    'nozzles.nozzle_code',
                    DB::raw(
                        'COUNT(transactions.id) as transaction_count'
                    ),
                    DB::raw(
                        'SUM(transactions.volume_litres) as total_litres'
                    ),
                    DB::raw(
                        'SUM(transactions.amount_minor) as total_minor'
                    )
                )
                ->groupBy(
                    'transactions.nozzle_id',
                    'nozzles.nozzle_code'
                )
                ->get()
                ->map(
                    fn ($row) => [
                        'nozzle_id' =>
                            $row->nozzle_id,

                        'nozzle_code' =>
                            $row->nozzle_code,

                        'transaction_count' =>
                            (int) $row->transaction_count,

                        'total_litres' =>
                            number_format(
                                (float) $row->total_litres,
                                3,
                                '.',
                                ''
                            ),

                        'total_minor' =>
                            (int) $row->total_minor,

                        'total_naira' =>
                            number_format(
                                $row->total_minor / 100,
                                2,
                                '.',
                                ''
                            ),
                    ]
                )
                ->values();

        /*
         * Sales by attendant.
         */
        $salesByAttendant =
            Transaction::query()
                ->where(
                    'transactions.station_id',
                    $station->id
                )
                ->where(
                    'transactions.tenant_id',
                    $station->tenant_id
                )
                ->where(
                    'transactions.status',
                    'COMPLETED'
                )
                ->whereBetween(
                    'transactions.completed_at',
                    [
                        $dayStart,
                        $dayEnd,
                    ]
                )
                ->join(
                    'users',
                    'users.id',
                    '=',
                    'transactions.attendant_user_id'
                )
                ->select(
                    'transactions.attendant_user_id',
                    'users.user_code',
                    'users.name',
                    DB::raw(
                        'COUNT(transactions.id) as transaction_count'
                    ),
                    DB::raw(
                        'SUM(transactions.volume_litres) as total_litres'
                    ),
                    DB::raw(
                        'SUM(transactions.amount_minor) as total_minor'
                    )
                )
                ->groupBy(
                    'transactions.attendant_user_id',
                    'users.user_code',
                    'users.name'
                )
                ->get()
                ->map(
                    fn ($row) => [
                        'attendant_user_id' =>
                            $row->attendant_user_id,

                        'user_code' =>
                            $row->user_code,

                        'name' =>
                            $row->name,

                        'transaction_count' =>
                            (int) $row->transaction_count,

                        'total_litres' =>
                            number_format(
                                (float) $row->total_litres,
                                3,
                                '.',
                                ''
                            ),

                        'total_minor' =>
                            (int) $row->total_minor,

                        'total_naira' =>
                            number_format(
                                $row->total_minor / 100,
                                2,
                                '.',
                                ''
                            ),
                    ]
                )
                ->values();

        /*
         * Shift activity during the day.
         *
         * Count shifts based on operational timestamps,
         * not only created_at.
         */
        $shiftsOpened =
            Shift::query()
                ->where(
                    'station_id',
                    $station->id
                )
                ->where(
                    'tenant_id',
                    $station->tenant_id
                )
                ->whereBetween(
                    'opened_at',
                    [
                        $dayStart,
                        $dayEnd,
                    ]
                )
                ->count();

        $shiftsActivated =
            Shift::query()
                ->where(
                    'station_id',
                    $station->id
                )
                ->where(
                    'tenant_id',
                    $station->tenant_id
                )
                ->whereBetween(
                    'activated_at',
                    [
                        $dayStart,
                        $dayEnd,
                    ]
                )
                ->count();

        $shiftsSealed =
            Shift::query()
                ->where(
                    'station_id',
                    $station->id
                )
                ->where(
                    'tenant_id',
                    $station->tenant_id
                )
                ->where(
                    'status',
                    'SEALED'
                )
                ->whereBetween(
                    'closed_at',
                    [
                        $dayStart,
                        $dayEnd,
                    ]
                )
                ->count();

        /*
         * Reconciliation totals for reports
         * generated on this day.
         */
        $reconciliationBase =
            ReconciliationReport::query()
                ->where(
                    'station_id',
                    $station->id
                )
                ->where(
                    'tenant_id',
                    $station->tenant_id
                )
                ->whereBetween(
                    'generated_at',
                    [
                        $dayStart,
                        $dayEnd,
                    ]
                );

        $reconciliationCount =
            (clone $reconciliationBase)
                ->count();

        $volumeVariance =
            (clone $reconciliationBase)
                ->sum(
                    'volume_variance_litres'
                );

        $cashVarianceMinor =
            (clone $reconciliationBase)
                ->sum(
                    'cash_variance_minor'
                );

        /*
         * Discrepancies created during this day.
         */
        $discrepancyCount =
            ShiftDiscrepancy::query()
                ->where(
                    'station_id',
                    $station->id
                )
                ->where(
                    'tenant_id',
                    $station->tenant_id
                )
                ->whereBetween(
                    'created_at',
                    [
                        $dayStart,
                        $dayEnd,
                    ]
                )
                ->count();

        $openDiscrepancyCount =
            ShiftDiscrepancy::query()
                ->where(
                    'station_id',
                    $station->id
                )
                ->where(
                    'tenant_id',
                    $station->tenant_id
                )
                ->whereBetween(
                    'created_at',
                    [
                        $dayStart,
                        $dayEnd,
                    ]
                )
                ->whereIn(
                    'status',
                    [
                        'OPEN',
                        'ESCALATED',
                    ]
                )
                ->count();

        return response()->json([
            'data' => [
                'report' => [
                    'report_type' =>
                        'DAILY_STATION_REPORT',

                    'date' =>
                        $reportDate->format(
                            'Y-m-d'
                        ),

                    'generated_at' =>
                        now()->toISOString(),
                ],

                'station' => [
                    'id' =>
                        $station->id,

                    'station_code' =>
                        $station->station_code,

                    'name' =>
                        $station->name,

                    'status' =>
                        $station->status,
                ],

                'sales_summary' => [
                    'transaction_count' =>
                        $transactionCount,

                    'total_litres' =>
                        number_format(
                            (float) $totalLitres,
                            3,
                            '.',
                            ''
                        ),

                    'total_revenue_minor' =>
                        (int) $totalRevenueMinor,

                    'total_revenue_naira' =>
                        number_format(
                            $totalRevenueMinor / 100,
                            2,
                            '.',
                            ''
                        ),
                ],

                'payment_summary' =>
                    $paymentSummary,

                'sales_by_product' =>
                    $salesByProduct,

                'sales_by_nozzle' =>
                    $salesByNozzle,

                'sales_by_attendant' =>
                    $salesByAttendant,

                'shift_summary' => [
                    'opened' =>
                        $shiftsOpened,

                    'activated' =>
                        $shiftsActivated,

                    'sealed' =>
                        $shiftsSealed,
                ],

                'reconciliation_summary' => [
                    'reports_generated' =>
                        $reconciliationCount,

                    'volume_variance_litres' =>
                        number_format(
                            (float) $volumeVariance,
                            3,
                            '.',
                            ''
                        ),

                    'cash_variance_minor' =>
                        (int) $cashVarianceMinor,

                    'cash_variance_naira' =>
                        number_format(
                            $cashVarianceMinor / 100,
                            2,
                            '.',
                            ''
                        ),
                ],

                'discrepancy_summary' => [
                    'total' =>
                        $discrepancyCount,

                    'open_or_escalated' =>
                        $openDiscrepancyCount,
                ],
            ],
        ]);
    }
}