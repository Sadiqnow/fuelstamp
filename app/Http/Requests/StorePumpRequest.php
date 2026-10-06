<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePumpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('STATION_OWNER')
            || $this->user()?->hasRole('PLATFORM_ADMIN');
    }

    public function rules(): array
    {
        return [
            'pump_code' => ['required', 'string', 'max:32'],
        ];
    }
}