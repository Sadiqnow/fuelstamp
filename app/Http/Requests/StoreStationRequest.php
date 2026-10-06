<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('STATION_OWNER')
            || $this->user()?->hasRole('PLATFORM_ADMIN');
    }

    public function rules(): array
    {
        $isPlatformAdmin = $this->user()?->hasRole('PLATFORM_ADMIN') ?? false;
        $tenantId = $isPlatformAdmin
            ? $this->input('tenant_id')
            : $this->user()?->tenant_id;

        return [
            'tenant_id' => [
                Rule::requiredIf($isPlatformAdmin),
                Rule::prohibitedIf(! $isPlatformAdmin),
                'uuid',
                Rule::exists('tenants', 'id'),
            ],
            'station_code' => [
                'required',
                'string',
                'max:32',
                Rule::unique('stations', 'station_code')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'name' => ['required', 'string', 'max:180'],
            'address' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'license_expiry' => ['nullable', 'date'],
        ];
    }
}