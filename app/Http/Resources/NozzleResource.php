<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NozzleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pump_id' => $this->pump_id,
            'nozzle_code' => $this->nozzle_code,
            'status' => $this->status,

            'fuel_product' => $this->whenLoaded(
                'fuelProduct',
                fn () => [
                    'id' => $this->fuelProduct->id,
                    'code' => $this->fuelProduct->code,
                    'name' => $this->fuelProduct->name,
                ]
            ),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}