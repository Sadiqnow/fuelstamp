<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FuelPriceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'station_id' => $this->station_id,

            'fuel_product' => $this->whenLoaded(
                'fuelProduct',
                fn () => [
                    'id' => $this->fuelProduct->id,
                    'code' => $this->fuelProduct->code,
                    'name' => $this->fuelProduct->name,
                ]
            ),

            'price_minor_per_litre' =>
                $this->price_minor_per_litre,

            /*
             * Convenience display value only.
             * Financial calculations should continue
             * using price_minor_per_litre.
             */
            'price_naira_per_litre' =>
                number_format(
                    $this->price_minor_per_litre / 100,
                    2,
                    '.',
                    ''
                ),

            'effective_from' =>
                $this->effective_from?->toISOString(),

            'effective_to' =>
                $this->effective_to?->toISOString(),

            'created_by_user_id' =>
                $this->created_by_user_id,

            'created_at' =>
                $this->created_at?->toISOString(),
        ];
    }
}