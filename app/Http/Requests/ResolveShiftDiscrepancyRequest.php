<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveShiftDiscrepancyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;

    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    'EXPLAINED',
                    'RESOLVED',
                    'ESCALATED',
                ]),
            ],

            'resolution_notes' => [
                'required',
                'string',
                'max:3000',
            ],
        ];
    }
}