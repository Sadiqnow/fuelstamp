<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shift_id' => $this->shift_id,
            'assignment_type' => $this->assignment_type,
            'status' => $this->status,

            'user' => $this->whenLoaded(
                'user',
                fn () => [
                    'id' => $this->user->id,
                    'user_code' => $this->user->user_code,
                    'name' => $this->user->name,
                    'email' => $this->user->email,
                ]
            ),

            'assigned_at' =>
                $this->assigned_at?->toISOString(),

            'accepted_at' =>
                $this->accepted_at?->toISOString(),

            'released_at' =>
                $this->released_at?->toISOString(),
        ];
    }
}