<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFuelCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'station_id' => [
                'required',
                'uuid',
                'exists:stations,id',
            ],

            'fuel_product_id' => [
                'required',
                'integer',
                'exists:fuel_products,id',
            ],

            'authorization_mode' => [
                'required',
                Rule::in([
                    'AMOUNT',
                    'LITRES',
                ]),
            ],

            /*
             * Kobo.
             */
            'amount_minor' => [
                'required_if:authorization_mode,AMOUNT',
                'nullable',
                'integer',
                'min:1',
            ],

            'volume_litres' => [
                'required_if:authorization_mode,LITRES',
                'nullable',
                'numeric',
                'gt:0',
            ],

            /*
             * 5–60 minute authorization.
             */
            'ttl_minutes' => [
                'nullable',
                'integer',
                'min:5',
                'max:60',
            ],

            'idempotency_key' => [
                'required',
                'string',
                'max:100',
            ],
        ];
    }
}