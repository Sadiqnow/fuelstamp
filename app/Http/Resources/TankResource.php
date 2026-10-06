<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TankResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'station_id' => $this->station_id,

            'tank_code' => $this->tank_code,

            'fuel_product' => $this->whenLoaded(
                'fuelProduct',
                fn () => [
                    'id' => $this->fuelProduct->id,
                    'code' => $this->fuelProduct->code,
                    'name' => $this->fuelProduct->name,
                ]
            ),

            'capacity_litres' => $this->capacity_litres,

            'low_level_threshold_litres' =>
                $this->low_level_threshold_litres,

            'status' => $this->status,

            'created_at' =>
                $this->created_at?->toISOString(),
        ];
    }
}