<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeterReadingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shift_id' => $this->shift_id,
            'nozzle_id' => $this->nozzle_id,

            'reading_type' => $this->reading_type,

            'meter_litres' => $this->meter_litres,

            'captured_by_user_id' =>
                $this->captured_by_user_id,

            'captured_at' =>
                $this->captured_at?->toISOString(),

            'evidence_path' =>
                $this->evidence_path,

            'notes' =>
                $this->notes,
        ];
    }
}