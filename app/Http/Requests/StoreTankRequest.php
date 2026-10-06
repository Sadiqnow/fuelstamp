<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('STATION_OWNER')
            || $this->user()?->hasRole('PLATFORM_ADMIN');
    }

    public function rules(): array
    {
        return [
            'tank_code' => [
                'required',
                'string',
                'max:32',
            ],

            'fuel_product_id' => [
                'required',
                'integer',
                'exists:fuel_products,id',
            ],

            'capacity_litres' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999999.999',
            ],

            'low_level_threshold_litres' => [
                'nullable',
                'numeric',
                'gte:0',
                'lt:capacity_litres',
            ],
        ];
    }
}