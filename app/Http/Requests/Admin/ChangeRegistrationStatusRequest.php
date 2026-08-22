<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeRegistrationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    'ACCEPTED',
                    'REJECTED',
                    'WITHDRAWN',
                ]),
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' =>
                'Status tujuan wajib dipilih.',

            'status.in' =>
                'Status tujuan tidak valid.',

            'notes.max' =>
                'Catatan maksimal 1000 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->filled('status')
                ? strtoupper(
                    trim(
                        (string) $this->input('status')
                    )
                )
                : null,

            'notes' => $this->filled('notes')
                ? trim(
                    (string) $this->input('notes')
                )
                : null,
        ]);
    }
}