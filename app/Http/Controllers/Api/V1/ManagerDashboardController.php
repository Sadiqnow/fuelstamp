<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ReconciliationReport;
use App\Models\Shift;
use App\Models\ShiftDiscrepancy;
use App\Models\Station;
use App\Models\StationStaff;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ManagerDashboardController extends Controller
{
    public function show(
        Station $station
    ): JsonResponse {
        $user = request()->user();

        /*
         * Tenant boundary
         */
        abort_if(
            $station->tenant_id !== $user->tenant_id,
            403,
            'You do not have access to this station.'
        );

        /*
         * Platform Admin may inspect.
         * Station Manager must be active staff at station.
         */
        $isAdmin =
            $user->hasRole('PLATFORM_ADMIN');

        if (! $isAdmin) {
            abort_unless(
                $user->hasRole('STATION_MANAGER'),
                403,
                'You are not authorized to view this dashboard.'
            );

            $activeManager =
                StationStaff::query()
                    ->where(
                        'station_id',
                        $station->id
                    )
                    ->where(
                        'user_id',
                        $user->id
                    )
                    ->where(
                        'staff_type',
                        'STATION_MANAGER'
                    )
                    ->where(
                        'status',
                        'ACTIVE'
                    )
                    ->exists();

            abort_unless(
                $activeManager,
                403,
                'You are not an active manager at this station.'
            );
        }

        $today =
            Carbon::today();

        $dayStart =
            $today->copy()->startOfDay();

        $dayEnd =
            $today->copy()->endOfDay();

        /*
         * Today's canonical sales
         */
        $transactions =
            Transaction::query()
                ->where(
                    'tenant_id',
                    $station->tenant_id
                )
                ->where(
                    'station_id',
                    $station->id
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
            (clone $transactions)->count();

        $totalLitres =
            (clone $transactions)
                ->sum('volume_litres');

        $totalRevenueMinor =
            (clone $transactions)
                ->sum('amount_minor');

        /*
         * Payment breakdown
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
                    )
                )
                ->groupBy(
                    'transaction_payments.payment_method'
                )
                ->get()
                ->keyBy('payment_method');

        $payment = function (
            string $method
        ) use ($paymentTotals): int {
            return (int) (
                $paymentTotals
                    ->get($method)
                    ?->total_minor ?? 0
            );
        };

        /*
         * Current active operational shift
         */
        $activeShift =
            Shift::query()
                ->where(
                    'tenant_id',
                    $station->tenant_id
                )
                ->where(
                    'station_id',
                    $station->id
                )
                ->whereIn(
                    'status',
                    [
                        'OPEN',
                        'ACTIVE',
                        'CLOSING',
                        'RECONCILIATION_PENDING',
                    ]
                )
                ->latest(
                    'created_at'
                )
                ->first();

        /*
         * Pending reconciliation work
         */
        $pendingReconciliations =
            ReconciliationReport::query()
                ->where(
                    'tenant_id',
                    $station->tenant_id
                )
                ->where(
                    'station_id',
                    $station->id
                )
                ->whereIn(
                    'status',
                    [
                        'PENDING_REVIEW',
                        'DISCREPANCY_OPEN',
                    ]
                )
                ->count();

        /*
         * Open discrepancy count
         */
        $openDiscrepancies =
            ShiftDiscrepancy::query()
                ->where(
                    'tenant_id',
                    $station->tenant_id
                )
                ->where(
                    'station_id',
                    $station->id
                )
                ->whereIn(
                    'status',
                    [
                        'OPEN',
                        'ESCALATED',
                    ]
                )
                ->count();

        /*
         * Top attendant today
         */
        $topAttendant =
            Transaction::query()
                ->where(
                    'transactions.tenant_id',
                    $station->tenant_id
                )
                ->where(
                    'transactions.station_id',
                    $station->id
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
                ->orderByDesc(
                    'total_minor'
                )
                ->first();

        /*
         * Top nozzle today
         */
        $topNozzle =
            Transaction::query()
                ->where(
                    'transactions.tenant_id',
                    $station->tenant_id
                )
                ->where(
                    'transactions.station_id',
                    $station->id
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
                ->orderByDesc(
                    'total_litres'
                )
                ->first();

        /*
         * Recent transactions
         */
        $recentTransactions =
            Transaction::query()
                ->where(
                    'tenant_id',
                    $station->tenant_id
                )
                ->where(
                    'station_id',
                    $station->id
                )
                ->with([
                    'attendant',
                    'nozzle',
                    'fuelProduct',
                    'payment',
                ])
                ->latest(
                    'completed_at'
                )
                ->limit(5)
                ->get()
                ->map(
                    fn ($tx) => [
                        'id' =>
                            $tx->id,

                        'transaction_code' =>
                            $tx->transaction_code,

                        'lane_type' =>
                            $tx->lane_type,

                        'attendant' =>
                            $tx->attendant?->name,

                        'nozzle' =>
                            $tx->nozzle?->nozzle_code,

                        'fuel_product' =>
                            $tx->fuelProduct?->code,

                        'volume_litres' =>
                            $tx->volume_litres,

                        'amount_minor' =>
                            $tx->amount_minor,

                        'amount_naira' =>
                            number_format(
                                $tx->amount_minor / 100,
                                2,
                                '.',
                                ''
                            ),

                        'payment_method' =>
                            $tx->payment?->payment_method,

                        'completed_at' =>
                            $tx
                                ->completed_at
                                ?->toISOString(),
                    ]
                )
                ->values();

        /*
         * Attention centre
         */
        $attentionItems = [];

        if ($openDiscrepancies > 0) {
            $attentionItems[] = [
                'type' =>
                    'DISCREPANCY',

                'severity' =>
                    'HIGH',

                'title' =>
                    'Open shift discrepancies',

                'message' =>
                    $openDiscrepancies .
                    ' discrepancy record(s) require review.',

                'action' =>
                    'REVIEW_DISCREPANCIES',
            ];
        }

        if ($pendingReconciliations > 0) {
            $attentionItems[] = [
                'type' =>
                    'RECONCILIATION',

                'severity' =>
                    'MEDIUM',

                'title' =>
                    'Reconciliation pending',

                'message' =>
                    $pendingReconciliations .
                    ' reconciliation report(s) require action.',

                'action' =>
                    'REVIEW_RECONCILIATION',
            ];
        }

        if (
            $activeShift &&
            $activeShift->status === 'CLOSING'
        ) {
            $attentionItems[] = [
                'type' =>
                    'SHIFT',

                'severity' =>
                    'MEDIUM',

                'title' =>
                    'Shift awaiting reconciliation',

                'message' =>
                    'The current shift is in the closing process.',

                'action' =>
                    'CONTINUE_SHIFT_CLOSE',
            ];
        }

        return response()->json([
            'data' => [
                'generated_at' =>
                    now()->toISOString(),

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

                'today' => [
                    'date' =>
                        $today->format('Y-m-d'),

                    'revenue_minor' =>
                        (int) $totalRevenueMinor,

                    'revenue_naira' =>
                        number_format(
                            $totalRevenueMinor / 100,
                            2,
                            '.',
                            ''
                        ),

                    'litres_sold' =>
                        number_format(
                            (float) $totalLitres,
                            3,
                            '.',
                            ''
                        ),

                    'transaction_count' =>
                        $transactionCount,
                ],

                'payment_kpis' => [
                    'cash_minor' =>
                        $payment('CASH'),

                    'cash_naira' =>
                        number_format(
                            $payment('CASH') / 100,
                            2,
                            '.',
                            ''
                        ),

                    'pos_minor' =>
                        $payment('POS'),

                    'pos_naira' =>
                        number_format(
                            $payment('POS') / 100,
                            2,
                            '.',
                            ''
                        ),

                    'transfer_minor' =>
                        $payment('TRANSFER'),

                    'transfer_naira' =>
                        number_format(
                            $payment('TRANSFER') / 100,
                            2,
                            '.',
                            ''
                        ),

                    'other_minor' =>
                        $payment('OTHER'),

                    'other_naira' =>
                        number_format(
                            $payment('OTHER') / 100,
                            2,
                            '.',
                            ''
                        ),
                ],

                'active_shift' =>
                    $activeShift
                        ? [
                            'id' =>
                                $activeShift->id,

                            'shift_code' =>
                                $activeShift->shift_code,

                            'status' =>
                                $activeShift->status,

                            'opened_at' =>
                                $activeShift
                                    ->opened_at
                                    ?->toISOString(),

                            'activated_at' =>
                                $activeShift
                                    ->activated_at
                                    ?->toISOString(),
                        ]
                        : null,

                'operational_kpis' => [
                    'pending_reconciliations' =>
                        $pendingReconciliations,

                    'open_discrepancies' =>
                        $openDiscrepancies,
                ],

                'top_attendant' =>
                    $topAttendant
                        ? [
                            'user_id' =>
                                $topAttendant
                                    ->attendant_user_id,

                            'user_code' =>
                                $topAttendant
                                    ->user_code,

                            'name' =>
                                $topAttendant
                                    ->name,

                            'transaction_count' =>
                                (int)
                                $topAttendant
                                    ->transaction_count,

                            'total_litres' =>
                                number_format(
                                    (float)
                                    $topAttendant
                                        ->total_litres,
                                    3,
                                    '.',
                                    ''
                                ),

                            'total_naira' =>
                                number_format(
                                    $topAttendant
                                        ->total_minor / 100,
                                    2,
                                    '.',
                                    ''
                                ),
                        ]
                        : null,

                'top_nozzle' =>
                    $topNozzle
                        ? [
                            'nozzle_id' =>
                                $topNozzle
                                    ->nozzle_id,

                            'nozzle_code' =>
                                $topNozzle
                                    ->nozzle_code,

                            'transaction_count' =>
                                (int)
                                $topNozzle
                                    ->transaction_count,

                            'total_litres' =>
                                number_format(
                                    (float)
                                    $topNozzle
                                        ->total_litres,
                                    3,
                                    '.',
                                    ''
                                ),

                            'total_naira' =>
                                number_format(
                                    $topNozzle
                                        ->total_minor / 100,
                                    2,
                                    '.',
                                    ''
                                ),
                        ]
                        : null,

                'attention_items' =>
                    $attentionItems,

                'recent_transactions' =>
                    $recentTransactions,
            ],
        ]);
    }
}