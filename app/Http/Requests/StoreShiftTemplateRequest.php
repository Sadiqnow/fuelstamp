<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('STATION_OWNER')
            || $this->user()?->hasRole('PLATFORM_ADMIN');
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'duration_minutes' => [
                'required',
                'integer',
                'min:60',
                'max:1440',
            ],

            'break_minutes' => [
                'nullable',
                'integer',
                'min:0',
                'max:240',
            ],

            'reconciliation_window_minutes' => [
                'nullable',
                'integer',
                'min:0',
                'max:240',
            ],
        ];
    }
}