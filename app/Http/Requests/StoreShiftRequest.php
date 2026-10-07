<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('STATION_MANAGER');
    }

    public function rules(): array
    {
        return [
            'shift_template_id' => [
                'required',
                'uuid',
                'exists:shift_templates,id',
            ],

            'scheduled_start_at' => [
                'nullable',
                'date',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}