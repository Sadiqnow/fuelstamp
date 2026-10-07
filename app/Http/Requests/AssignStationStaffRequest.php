<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignStationStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('STATION_OWNER')
            || $this->user()?->hasRole('PLATFORM_ADMIN');
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'uuid',
                'exists:users,id',
            ],

            'staff_type' => [
                'required',
                Rule::in([
                    'STATION_OWNER',
                    'STATION_MANAGER',
                    'ATTENDANT',
                ]),
            ],
        ];
    }
}