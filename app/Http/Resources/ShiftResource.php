<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shift_code' => $this->shift_code,

            'tenant_id' => $this->tenant_id,
            'station_id' => $this->station_id,

            'status' => $this->status,

            'shift_template' => $this->whenLoaded(
                'shiftTemplate',
                fn () => [
                    'id' => $this->shiftTemplate?->id,
                    'name' => $this->shiftTemplate?->name,
                    'duration_minutes' =>
                        $this->shiftTemplate?->duration_minutes,
                ]
            ),

            'manager' => $this->whenLoaded(
                'manager',
                fn () => [
                    'id' => $this->manager->id,
                    'user_code' =>
                        $this->manager->user_code,
                    'name' =>
                        $this->manager->name,
                ]
            ),
            

            'assignments' =>
                ShiftAssignmentResource::collection(
                    $this->whenLoaded('assignments')
                ),

            'nozzle_assignments' =>
                ShiftNozzleAssignmentResource::collection(
                    $this->whenLoaded('nozzleAssignments')
                ),

            'meter_readings' =>
                MeterReadingResource::collection(
                    $this->whenLoaded('meterReadings')
                ),

            'scheduled_start_at' =>
                $this->scheduled_start_at?->toISOString(),

            'opened_at' =>
                $this->opened_at?->toISOString(),

            'activated_at' =>
                $this->activated_at?->toISOString(),

            'closing_started_at' =>
                $this->closing_started_at?->toISOString(),

            'closed_at' =>
                $this->closed_at?->toISOString(),

            'notes' => $this->notes,

            'created_at' =>
                $this->created_at?->toISOString(),
        ];
    }
}