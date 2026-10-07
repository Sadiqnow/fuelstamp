<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AssignShiftNozzleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('STATION_MANAGER');
    }

    public function rules(): array
    {
        return [
            'nozzle_id' => [
                'required',
                'uuid',
                'exists:nozzles,id',
            ],

            'attendant_user_id' => [
                'required',
                'uuid',
                'exists:users,id',
            ],
        ];
    }
}