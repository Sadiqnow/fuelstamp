<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClosingMeterReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(
            'STATION_MANAGER'
        );
    }

    public function rules(): array
    {
        return [
            'nozzle_id' => [
                'required',
                'uuid',
                'exists:nozzles,id',
            ],

            'meter_litres' => [
                'required',
                'numeric',
                'gte:0',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}