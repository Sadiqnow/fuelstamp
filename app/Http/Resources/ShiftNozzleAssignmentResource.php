<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftNozzleAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shift_id' => $this->shift_id,

            'custody_status' => $this->custody_status,

            'nozzle' => $this->whenLoaded(
                'nozzle',
                fn () => [
                    'id' => $this->nozzle->id,
                    'nozzle_code' => $this->nozzle->nozzle_code,
                    'pump_id' => $this->nozzle->pump_id,
                ]
            ),

            'attendant' => $this->whenLoaded(
                'attendant',
                fn () => [
                    'id' => $this->attendant->id,
                    'user_code' => $this->attendant->user_code,
                    'name' => $this->attendant->name,
                ]
            ),

            'assigned_at' =>
                $this->assigned_at?->toISOString(),

            'accepted_at' =>
                $this->accepted_at?->toISOString(),
        ];
    }
}