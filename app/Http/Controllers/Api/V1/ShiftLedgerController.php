<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ShiftLedgerController extends Controller
{
    public function summary(
        Shift $shift
    ): JsonResponse {
        $this->ensureManagerScope($shift);

        $base = Transaction::query()
            ->where('shift_id', $shift->id)
            ->where('status', 'COMPLETED');

        $transactionCount = (clone $base)->count();

        $totalLitres = (clone $base)
            ->sum('volume_litres');

        $totalRevenueMinor = (clone $base)
            ->sum('amount_minor');

        /*
         * Payment totals
         */
        $paymentTotals = DB::table(
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
                'transaction_payments.status',
                'CONFIRMED'
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

        $cashMinor = (int) (
            $paymentTotals['CASH']->total_minor ?? 0
        );

        $posMinor = (int) (
            $paymentTotals['POS']->total_minor ?? 0
        );

        $transferMinor = (int) (
            $paymentTotals['TRANSFER']->total_minor ?? 0
        );

        $otherMinor = (int) (
            $paymentTotals['OTHER']->total_minor ?? 0
        );

        /*
         * Sales by nozzle
         */
        $salesByNozzle = Transaction::query()
            ->where(
                'transactions.shift_id',
                $shift->id
            )
            ->where(
                'transactions.status',
                'COMPLETED'
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
            ->orderBy(
                'nozzles.nozzle_code'
            )
            ->get()
            ->map(function ($row) {
                return [
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
                ];
            })
            ->values();

        /*
         * Sales by attendant
         */
        $salesByAttendant = Transaction::query()
            ->where(
                'transactions.shift_id',
                $shift->id
            )
            ->where(
                'transactions.status',
                'COMPLETED'
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
            ->orderBy(
                'users.name'
            )
            ->get()
            ->map(function ($row) {
                return [
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
                ];
            })
            ->values();

        /*
         * Sales by fuel product
         */
        $salesByProduct = Transaction::query()
            ->where(
                'transactions.shift_id',
                $shift->id
            )
            ->where(
                'transactions.status',
                'COMPLETED'
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
            ->map(function ($row) {
                return [
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
                ];
            })
            ->values();

        /*
         * Payment breakdown for UI
         */
        $payments = collect([
            'CASH' => $cashMinor,
            'POS' => $posMinor,
            'TRANSFER' => $transferMinor,
            'OTHER' => $otherMinor,
        ])->map(
            fn ($minor, $method) => [
                'payment_method' =>
                    $method,

                'amount_minor' =>
                    $minor,

                'amount_naira' =>
                    number_format(
                        $minor / 100,
                        2,
                        '.',
                        ''
                    ),

                'transaction_count' =>
                    (int) (
                        $paymentTotals[$method]
                            ->transaction_count
                        ?? 0
                    ),
            ]
        )->values();

        return response()->json([
            'data' => [
                'shift' => [
                    'id' =>
                        $shift->id,

                    'shift_code' =>
                        $shift->shift_code,

                    'status' =>
                        $shift->status,

                    'station_id' =>
                        $shift->station_id,

                    'manager_user_id' =>
                        $shift->manager_user_id,
                ],

                'totals' => [
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
                    $payments,

                'sales_by_nozzle' =>
                    $salesByNozzle,

                'sales_by_attendant' =>
                    $salesByAttendant,

                'sales_by_product' =>
                    $salesByProduct,

                /*
                 * For later meter reconciliation:
                 * expected meter movement is total
                 * transaction litres by nozzle.
                 */
                'expected_meter_movement_litres' =>
                    $salesByNozzle->map(
                        fn ($row) => [
                            'nozzle_id' =>
                                $row['nozzle_id'],

                            'nozzle_code' =>
                                $row['nozzle_code'],

                            'expected_litres' =>
                                $row['total_litres'],
                        ]
                    )->values(),
            ],
        ]);
    }

    private function ensureManagerScope(
        Shift $shift
    ): void {
        $user = request()->user();

        abort_if(
            $shift->tenant_id !==
                $user->tenant_id,
            403,
            'You do not have access to this shift.'
        );

        abort_if(
            $shift->manager_user_id !==
                $user->id,
            403,
            'You are not the manager responsible for this shift.'
        );
    }
}