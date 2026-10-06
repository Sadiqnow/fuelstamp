<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNozzleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('STATION_OWNER')
            || $this->user()?->hasRole('PLATFORM_ADMIN');
    }

    public function rules(): array
    {
        return [
            'nozzle_code' => ['required', 'string', 'max:32'],
            'fuel_product_id' => [
                'required',
                'integer',
                'exists:fuel_products,id',
            ],
        ];
    }
}