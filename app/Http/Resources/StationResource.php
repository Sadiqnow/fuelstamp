<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'station_code' => $this->station_code,
            'name' => $this->name,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'status' => $this->status,
            'license_expiry' => optional($this->license_expiry)?->toDateString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}