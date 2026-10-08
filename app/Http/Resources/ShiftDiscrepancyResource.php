<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftDiscrepancyResource extends JsonResource
{
    public function toArray(
        Request $request
    ): array {
        return [
            'id' => $this->id,
            'shift_id' => $this->shift_id,

            'discrepancy_type' =>
                $this->discrepancy_type,

            'status' =>
                $this->status,

            'nozzle_id' =>
                $this->nozzle_id,

            'variance_litres' =>
                $this->variance_litres,

            'variance_minor' =>
                $this->variance_minor,

            'description' =>
                $this->description,

            'resolution_notes' =>
                $this->resolution_notes,

            'resolved_at' =>
                $this
                    ->resolved_at
                    ?->toISOString(),
        ];
    }
}