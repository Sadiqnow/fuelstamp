<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'station_id' => $this->station_id,

            'name' => $this->name,

            'duration_minutes' =>
                $this->duration_minutes,

            'break_minutes' =>
                $this->break_minutes,

            'reconciliation_window_minutes' =>
                $this->reconciliation_window_minutes,

            'active' => $this->active,

            'created_at' =>
                $this->created_at?->toISOString(),
        ];
    }
}