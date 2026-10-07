<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignShiftAttendantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('STATION_MANAGER');
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'uuid',
                'exists:users,id',
            ],
        ];
    }
}