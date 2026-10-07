<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTraditionalTransactionRequest;
use App\Http\Resources\TransactionResource;
use App\Models\FuelPriceSchedule;
use App\Models\ManualTransactionDetail;
use App\Models\Shift;
use App\Models\ShiftNozzleAssignment;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class TraditionalTransactionController extends Controller
{
    public function store(
        StoreTraditionalTransactionRequest $request,
        Shift $shift
    ): JsonResponse {
        $attendant = $request->user();

        /*
         * Transactions only happen in ACTIVE shifts.
         */
        if ($shift->status !== 'ACTIVE') {
            return response()->json([
                'error' => [
                    'code' => 'SHIFT_NOT_ACTIVE',
                    'message' =>
                        'Transactions can only be recorded against an ACTIVE shift.',
                    'details' => [
                        'shift_status' =>
                            $shift->status,
                    ],
                ],
            ], 409);
        }

        /*
         * Verify accepted nozzle custody.
         */
        $custody = ShiftNozzleAssignment::query()
            ->where(
                'shift_id',
                $shift->id
            )
            ->where(
                'nozzle_id',
                $request->nozzle_id
            )
            ->where(
                'attendant_user_id',
                $attendant->id
            )
            ->where(
                'custody_status',
                'ACCEPTED'
            )
            ->with(
                'nozzle.fuelProduct'
            )
            ->first();

        if (! $custody) {
            return response()->json([
                'error' => [
                    'code' =>
                        'NO_ACCEPTED_NOZZLE_CUSTODY',

                    'message' =>
                        'You do not have accepted custody of this nozzle for the active shift.',

                    'details' => [],
                ],
            ], 403);
        }

        /*
         * Idempotency protection.
         */
        $existing = Transaction::query()
            ->where(
                'tenant_id',
                $shift->tenant_id
            )
            ->where(
                'idempotency_key',
                $request->idempotency_key
            )
            ->with([
                'attendant',
                'nozzle',
                'fuelProduct',
                'manualDetail',
                'payment',
            ])
            ->first();

        if ($existing) {
            return response()->json([
                'data' =>
                    new TransactionResource(
                        $existing
                    ),

                'meta' => [
                    'idempotent_replay' =>
                        true,
                ],
            ]);
        }

        $nozzle = $custody->nozzle;

        $now = now();

        /*
         * Backend determines the effective station price.
         * Never trust price sent by a client.
         */
        $price = FuelPriceSchedule::query()
            ->where(
                'station_id',
                $shift->station_id
            )
            ->where(
                'fuel_product_id',
                $nozzle->fuel_product_id
            )
            ->where(
                'effective_from',
                '<=',
                $now
            )
            ->where(function ($query) use ($now) {
                $query
                    ->whereNull('effective_to')
                    ->orWhere(
                        'effective_to',
                        '>',
                        $now
                    );
            })
            ->orderByDesc(
                'effective_from'
            )
            ->first();

        if (! $price) {
            return response()->json([
                'error' => [
                    'code' =>
                        'NO_EFFECTIVE_FUEL_PRICE',

                    'message' =>
                        'There is no effective fuel price configured for this product.',

                    'details' => [],
                ],
            ], 422);
        }

        $unitPrice =
            $price->price_minor_per_litre;

        /*
         * Internally calculate using milli-litres
         * to preserve our 3 decimal litre precision.
         */
        if (
            $request->entry_mode === 'LITRES'
        ) {
            $milliLitres = (int) round(
                ((float) $request->litres) * 1000
            );

            $amountMinor = intdiv(
                ($unitPrice * $milliLitres) + 500,
                1000
            );

            $volumeLitres =
                $milliLitres / 1000;

            $enteredLitres =
                $volumeLitres;

            $enteredAmount =
                null;
        } else {
            $amountMinor =
                (int) $request->amount_minor;

            $milliLitres = intdiv(
                ($amountMinor * 1000)
                    + intdiv($unitPrice, 2),
                $unitPrice
            );

            $volumeLitres =
                $milliLitres / 1000;

            $enteredLitres =
                null;

            $enteredAmount =
                $amountMinor;
        }

        $transaction = DB::transaction(
            function () use (
                $request,
                $shift,
                $attendant,
                $nozzle,
                $unitPrice,
                $amountMinor,
                $volumeLitres,
                $enteredLitres,
                $enteredAmount
            ) {
                $transaction =
                    Transaction::create([
                        'tenant_id' =>
                            $shift->tenant_id,

                        'station_id' =>
                            $shift->station_id,

                        'shift_id' =>
                            $shift->id,

                        'attendant_user_id' =>
                            $attendant->id,

                        'nozzle_id' =>
                            $nozzle->id,

                        'fuel_product_id' =>
                            $nozzle->fuel_product_id,

                        'lane_type' =>
                            'TRADITIONAL',

                        'transaction_code' =>
                            $this->generateTransactionCode(),

                        'volume_litres' =>
                            $volumeLitres,

                        'unit_price_minor' =>
                            $unitPrice,

                        'amount_minor' =>
                            $amountMinor,

                        'status' =>
                            'COMPLETED',

                        'idempotency_key' =>
                            $request->idempotency_key,

                        'completed_at' =>
                            now(),
                    ]);

                ManualTransactionDetail::create([
                    'transaction_id' =>
                        $transaction->id,

                    'entry_mode' =>
                        $request->entry_mode,

                    'entered_litres' =>
                        $enteredLitres,

                    'entered_amount_minor' =>
                        $enteredAmount,

                    'notes' =>
                        $request->notes,
                ]);

                TransactionPayment::create([
                    'transaction_id' =>
                        $transaction->id,

                    'payment_method' =>
                        $request->payment_method,

                    'amount_minor' =>
                        $amountMinor,

                    'payment_reference' =>
                        $request->payment_reference,

                    'status' =>
                        'CONFIRMED',

                    'confirmed_at' =>
                        now(),
                ]);

                return $transaction;
            }
        );

        $transaction->load([
            'attendant',
            'nozzle',
            'fuelProduct',
            'manualDetail',
            'payment',
        ]);

        return response()->json([
            'data' =>
                new TransactionResource(
                    $transaction
                ),
        ], 201);
    }

    public function index(
        Shift $shift
    ): AnonymousResourceCollection {
        $user = request()->user();

        abort_if(
            $shift->tenant_id !== $user->tenant_id,
            403,
            'You do not have access to this shift.'
        );

        $transactions =
            Transaction::query()
                ->where(
                    'shift_id',
                    $shift->id
                )
                ->with([
                    'attendant',
                    'nozzle',
                    'fuelProduct',
                    'manualDetail',
                    'payment',
                ])
                ->latest(
                    'completed_at'
                )
                ->get();

        return TransactionResource::collection(
            $transactions
        );
    }

    private function generateTransactionCode(): string
    {
        return sprintf(
            'TX-%s-%s',
            now()->format('YmdHis'),
            strtoupper(
                substr(
                    str_replace(
                        '-',
                        '',
                        (string) \Illuminate\Support\Str::uuid()
                    ),
                    0,
                    6
                )
            )
        );
    }
}