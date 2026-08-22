<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReenrollmentPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'amount' => [
                'required',
                'integer',
                'min:1',
            ],

            'payment_method' => [
                'nullable',
                'string',
                Rule::in([
                    'CASH',
                    'TRANSFER',
                    'QRIS',
                    'OTHER',
                ]),
            ],

            'reference_number' => [
                'nullable',
                'string',
                'max:100',
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
            'amount.required' =>
                'Nominal pembayaran wajib diisi.',

            'amount.integer' =>
                'Nominal pembayaran harus berupa angka bulat.',

            'amount.min' =>
                'Nominal pembayaran minimal Rp1.',

            'payment_method.in' =>
                'Metode pembayaran tidak valid.',

            'reference_number.max' =>
                'Nomor referensi maksimal 100 karakter.',

            'notes.max' =>
                'Catatan maksimal 1000 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $amount = preg_replace(
            '/\D+/',
            '',
            (string) $this->input('amount')
        );

        $this->merge([
            'amount' => $amount !== ''
                ? (int) $amount
                : null,

            'payment_method' =>
                $this->filled('payment_method')
                    ? strtoupper(
                        trim(
                            (string) $this->input(
                                'payment_method'
                            )
                        )
                    )
                    : null,

            'reference_number' =>
                $this->filled('reference_number')
                    ? trim(
                        (string) $this->input(
                            'reference_number'
                        )
                    )
                    : null,

            'notes' =>
                $this->filled('notes')
                    ? trim(
                        (string) $this->input('notes')
                    )
                    : null,
        ]);
    }
}