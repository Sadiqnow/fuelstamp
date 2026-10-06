<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('STATION_OWNER')
            || $this->user()?->hasRole('PLATFORM_ADMIN');
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:180'],
            'address' => ['sometimes', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'license_expiry' => ['nullable', 'date'],
        ];
    }
}