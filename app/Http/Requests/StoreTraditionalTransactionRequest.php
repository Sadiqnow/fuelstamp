<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTraditionalTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(
            'ATTENDANT'
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

            'entry_mode' => [
                'required',
                Rule::in([
                    'LITRES',
                    'AMOUNT',
                ]),
            ],

            'litres' => [
                'required_if:entry_mode,LITRES',
                'nullable',
                'numeric',
                'gt:0',
            ],

            /*
             * Kobo.
             * ₦10,000 = 1000000
             */
            'amount_minor' => [
                'required_if:entry_mode,AMOUNT',
                'nullable',
                'integer',
                'min:1',
            ],

            'payment_method' => [
                'required',
                Rule::in([
                    'CASH',
                    'POS',
                    'TRANSFER',
                    'OTHER',
                ]),
            ],

            'payment_reference' => [
                'nullable',
                'string',
                'max:100',
            ],

            'idempotency_key' => [
                'required',
                'string',
                'max:100',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}