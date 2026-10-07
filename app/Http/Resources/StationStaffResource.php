<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StationStaffResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'station_id' => $this->station_id,

            'staff_type' => $this->staff_type,
            'status' => $this->status,

            'user' => $this->whenLoaded(
                'user',
                fn () => [
                    'id' => $this->user->id,
                    'user_code' => $this->user->user_code,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                    'status' => $this->user->status,
                ]
            ),

            'created_at' =>
                $this->created_at?->toISOString(),
        ];
    }
}