<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BuyerWalletResource;
use App\Http\Resources\WalletLedgerEntryResource;
use App\Models\BuyerWallet;
use App\Models\FuelProduct;
use App\Models\WalletAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class BuyerWalletController extends Controller
{
    public function show(): JsonResponse
    {
        $user = request()->user();

        abort_unless(
            $user->hasRole('BUYER'),
            403,
            'Buyer role required.'
        );

        $wallet = $this->getOrCreateWallet();

        $wallet->load([
            'user',
            'accounts.fuelProduct',
        ]);

        return response()->json([
            'data' =>
                new BuyerWalletResource(
                    $wallet
                ),
        ]);
    }

    public function ledger(): AnonymousResourceCollection
    {
        $user = request()->user();

        abort_unless(
            $user->hasRole('BUYER'),
            403,
            'Buyer role required.'
        );

        $wallet = $this->getOrCreateWallet();

        $entries =
            $wallet
                ->ledgerEntries()
                ->with('fuelProduct')
                ->latest('posted_at')
                ->paginate(50);

        return WalletLedgerEntryResource::collection(
            $entries
        );
    }

    private function getOrCreateWallet(): BuyerWallet
    {
        $user = request()->user();

        return DB::transaction(
            function () use ($user) {
                $wallet =
                    BuyerWallet::query()
                        ->where(
                            'tenant_id',
                            $user->tenant_id
                        )
                        ->where(
                            'user_id',
                            $user->id
                        )
                        ->first();

                if ($wallet) {
                    return $wallet;
                }

                $wallet =
                    BuyerWallet::create([
                        'tenant_id' =>
                            $user->tenant_id,

                        'user_id' =>
                            $user->id,

                        'wallet_code' =>
                            'FSW-' .
                            strtoupper(
                                substr(
                                    str_replace(
                                        '-',
                                        '',
                                        $user->id
                                    ),
                                    0,
                                    10
                                )
                            ),

                        'status' =>
                            'ACTIVE',

                        'activated_at' =>
                            now(),
                    ]);

                /*
                 * Every buyer receives:
                 * NGN
                 * CREDIT
                 * one litre account per fuel product.
                 */
                WalletAccount::create([
                    'wallet_id' =>
                        $wallet->id,

                    'asset_type' =>
                        'NGN',

                    'balance_minor' =>
                        0,
                ]);

                WalletAccount::create([
                    'wallet_id' =>
                        $wallet->id,

                    'asset_type' =>
                        'CREDIT',

                    'balance_minor' =>
                        0,
                ]);

                FuelProduct::query()
                    ->get()
                    ->each(
                        function ($product)
                        use ($wallet) {
                            WalletAccount::create([
                                'wallet_id' =>
                                    $wallet->id,

                                'asset_type' =>
                                    'LITRE',

                                'fuel_product_id' =>
                                    $product->id,

                                'balance_litres' =>
                                    0,
                            ]);
                        }
                    );

                return $wallet;
            }
        );
    }
}