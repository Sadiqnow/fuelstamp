<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FuelCodeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' =>
                $this->id,

            'code' =>
                $this->code,

            'status' =>
                $this->status,

            'authorization_mode' =>
                $this->authorization_mode,

            'asset_type' =>
                $this->asset_type,

            'authorized_amount_minor' =>
                $this->authorized_amount_minor,

            'authorized_amount_naira' =>
                $this->authorized_amount_minor !== null
                    ? number_format(
                        $this->authorized_amount_minor / 100,
                        2,
                        '.',
                        ''
                    )
                    : null,

            'authorized_volume_litres' =>
                $this->authorized_volume_litres,

            'station' =>
                $this->whenLoaded(
                    'station',
                    fn () => [
                        'id' =>
                            $this->station->id,

                        'station_code' =>
                            $this->station->station_code,

                        'name' =>
                            $this->station->name,
                    ]
                ),

            'fuel_product' =>
                $this->whenLoaded(
                    'fuelProduct',
                    fn () => [
                        'id' =>
                            $this->fuelProduct->id,

                        'code' =>
                            $this->fuelProduct->code,

                        'name' =>
                            $this->fuelProduct->name,
                    ]
                ),

            'expires_at' =>
                $this->expires_at?->toISOString(),

            'redeemed_at' =>
                $this->redeemed_at?->toISOString(),

            'redeemed_transaction_id' =>
                $this->redeemed_transaction_id,

            'cancelled_at' =>
                $this->cancelled_at?->toISOString(),

            'created_at' =>
                $this->created_at?->toISOString(),
        ];
    }
}