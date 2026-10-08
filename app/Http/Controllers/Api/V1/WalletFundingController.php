<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BuyerWallet;
use App\Models\WalletAccount;
use App\Models\WalletLedgerEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WalletFundingController extends Controller
{
    public function store(
        Request $request,
        BuyerWallet $wallet
    ): JsonResponse {
        abort_unless(
            $request->user()->hasRole(
                'PLATFORM_ADMIN'
            ),
            403,
            'Platform Admin role required.'
        );

        $validated = $request->validate([
            'asset_type' => [
                'required',
                Rule::in([
                    'NGN',
                    'CREDIT',
                    'LITRE',
                ]),
            ],

            'fuel_product_id' => [
                'nullable',
                'integer',
                'exists:fuel_products,id',
            ],

            'amount_minor' => [
                'nullable',
                'integer',
                'min:1',
            ],

            'volume_litres' => [
                'nullable',
                'numeric',
                'gt:0',
            ],

            'idempotency_key' => [
                'required',
                'string',
                'max:100',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ]);

        if (
            $validated['asset_type'] === 'LITRE'
            && empty(
                $validated['fuel_product_id']
            )
        ) {
            return response()->json([
                'error' => [
                    'code' =>
                        'FUEL_PRODUCT_REQUIRED',

                    'message' =>
                        'A litre wallet funding entry requires a fuel product.',

                    'details' => [],
                ],
            ], 422);
        }

        if (
            $validated['asset_type'] !== 'LITRE'
            && empty(
                $validated['amount_minor']
            )
        ) {
            return response()->json([
                'error' => [
                    'code' =>
                        'AMOUNT_REQUIRED',

                    'message' =>
                        'NGN and CREDIT funding require amount_minor.',

                    'details' => [],
                ],
            ], 422);
        }

        if (
            $validated['asset_type'] === 'LITRE'
            && empty(
                $validated['volume_litres']
            )
        ) {
            return response()->json([
                'error' => [
                    'code' =>
                        'VOLUME_REQUIRED',

                    'message' =>
                        'Litre funding requires volume_litres.',

                    'details' => [],
                ],
            ], 422);
        }

        $existing =
            WalletLedgerEntry::query()
                ->where(
                    'tenant_id',
                    $wallet->tenant_id
                )
                ->where(
                    'idempotency_key',
                    $validated['idempotency_key']
                )
                ->first();

        if ($existing) {
            return response()->json([
                'data' => $existing,
                'meta' => [
                    'idempotent_replay' =>
                        true,
                ],
            ]);
        }

        $entry = DB::transaction(
            function () use (
                $request,
                $wallet,
                $validated
            ) {
                $accountQuery =
                    WalletAccount::query()
                        ->where(
                            'wallet_id',
                            $wallet->id
                        )
                        ->where(
                            'asset_type',
                            $validated['asset_type']
                        );

                if (
                    $validated['asset_type']
                    === 'LITRE'
                ) {
                    $accountQuery->where(
                        'fuel_product_id',
                        $validated[
                            'fuel_product_id'
                        ]
                    );
                } else {
                    $accountQuery->whereNull(
                        'fuel_product_id'
                    );
                }

                /*
                 * Row lock prevents concurrent
                 * balance corruption.
                 */
                $account =
                    $accountQuery
                        ->lockForUpdate()
                        ->firstOrFail();

                if (
                    $validated['asset_type']
                    === 'LITRE'
                ) {
                    $newBalance =
                        round(
                            (float)
                            $account->balance_litres
                            +
                            (float)
                            $validated[
                                'volume_litres'
                            ],
                            3
                        );

                    $account->update([
                        'balance_litres' =>
                            $newBalance,
                    ]);

                    return WalletLedgerEntry::create([
                        'tenant_id' =>
                            $wallet->tenant_id,

                        'wallet_id' =>
                            $wallet->id,

                        'wallet_account_id' =>
                            $account->id,

                        'direction' =>
                            'CREDIT',

                        'asset_type' =>
                            'LITRE',

                        'fuel_product_id' =>
                            $validated[
                                'fuel_product_id'
                            ],

                        'volume_litres' =>
                            $validated[
                                'volume_litres'
                            ],

                        'balance_after_litres' =>
                            $newBalance,

                        'entry_type' =>
                            'FUNDING',

                        'idempotency_key' =>
                            $validated[
                                'idempotency_key'
                            ],

                        'created_by_user_id' =>
                            $request->user()->id,

                        'notes' =>
                            $validated['notes']
                            ?? null,

                        'posted_at' =>
                            now(),
                    ]);
                }

                $newBalance =
                    $account->balance_minor +
                    $validated['amount_minor'];

                $account->update([
                    'balance_minor' =>
                        $newBalance,
                ]);

                return WalletLedgerEntry::create([
                    'tenant_id' =>
                        $wallet->tenant_id,

                    'wallet_id' =>
                        $wallet->id,

                    'wallet_account_id' =>
                        $account->id,

                    'direction' =>
                        'CREDIT',

                    'asset_type' =>
                        $validated[
                            'asset_type'
                        ],

                    'amount_minor' =>
                        $validated[
                            'amount_minor'
                        ],

                    'balance_after_minor' =>
                        $newBalance,

                    'entry_type' =>
                        'FUNDING',

                    'idempotency_key' =>
                        $validated[
                            'idempotency_key'
                        ],

                    'created_by_user_id' =>
                        $request->user()->id,

                    'notes' =>
                        $validated['notes']
                        ?? null,

                    'posted_at' =>
                        now(),
                ]);
            }
        );

        return response()->json([
            'data' => $entry,
        ], 201);
    }
}