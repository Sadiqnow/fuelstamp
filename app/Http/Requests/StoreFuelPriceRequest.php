<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFuelPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('STATION_OWNER')
            || $this->user()?->hasRole('PLATFORM_ADMIN');
    }

    public function rules(): array
    {
        return [
            'fuel_product_id' => [
                'required',
                'integer',
                'exists:fuel_products,id',
            ],

            /*
             * Money is stored in kobo.
             *
             * Example:
             * ₦945.00/L = 94500
             */
            'price_minor_per_litre' => [
                'required',
                'integer',
                'min:1',
            ],

            'effective_from' => [
                'required',
                'date',
            ],
        ];
    }
}