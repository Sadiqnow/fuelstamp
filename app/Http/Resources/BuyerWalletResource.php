<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BuyerWalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_code' => $this->wallet_code,
            'status' => $this->status,

            'owner' => [
                'id' => $this->user?->id,
                'user_code' => $this->user?->user_code,
                'name' => $this->user?->name,
            ],

            'balances' =>
                $this->accounts
                    ->map(function ($account) {
                        return [
                            'account_id' =>
                                $account->id,

                            'asset_type' =>
                                $account->asset_type,

                            'fuel_product' =>
                                $account->fuelProduct
                                    ? [
                                        'id' =>
                                            $account
                                                ->fuelProduct
                                                ->id,

                                        'code' =>
                                            $account
                                                ->fuelProduct
                                                ->code,

                                        'name' =>
                                            $account
                                                ->fuelProduct
                                                ->name,
                                    ]
                                    : null,

                            'balance_minor' =>
                                $account->balance_minor,

                            'balance_naira' =>
                                $account->asset_type === 'NGN'
                                    ? number_format(
                                        $account->balance_minor / 100,
                                        2,
                                        '.',
                                        ''
                                    )
                                    : null,

                            'balance_litres' =>
                                $account->asset_type === 'LITRE'
                                    ? $account->balance_litres
                                    : null,

                            'status' =>
                                $account->status,
                        ];
                    })
                    ->values(),
        ];
    }
}