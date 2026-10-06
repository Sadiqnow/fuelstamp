<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PumpResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'station_id' => $this->station_id,
            'pump_code' => $this->pump_code,
            'status' => $this->status,

            'nozzles' => NozzleResource::collection(
                $this->whenLoaded('nozzles')
            ),

            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}