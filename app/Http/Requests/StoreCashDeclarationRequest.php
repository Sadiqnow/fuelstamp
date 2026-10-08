<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashDeclarationRequest extends FormRequest
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
            'declared_cash_minor' => [
                'required',
                'integer',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }
}