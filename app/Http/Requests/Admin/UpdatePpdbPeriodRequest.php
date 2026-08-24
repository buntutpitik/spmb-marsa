<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePpdbPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:20',
            ],

            'year_start' => [
                'required',
                'integer',
                'min:2020',
                'max:2100',
            ],

            'year_end' => [
                'required',
                'integer',
                'min:2020',
                'max:2101',
                'gt:year_start',
            ],

            'registration_open' => [
                'nullable',
                'date',
            ],

            'registration_close' => [
                'nullable',
                'date',
                'after_or_equal:registration_open',
            ],

            'status' => [
                'required',
                'string',
                Rule::in([
                    'DRAFT',
                    'OPEN',
                    'CLOSED',
                ]),
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],

            'principal_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'principal_nip' => [
                'nullable',
                'string',
                'max:50',
            ],

            'number_prefix' => [
                'required',
                'string',
                'max:30',
            ],

            'number_year' => [
                'required',
                'integer',
                'min:2020',
                'max:2100',
            ],

            'number_digits' => [
                'required',
                'integer',
                'min:3',
                'max:8',
            ],

            'include_major_code' => [
                'nullable',
                'boolean',
            ],

            'default_reenroll_fee' => [
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

    public function messages(): array
    {
        return [
            'name.required' =>
                'Nama periode wajib diisi.',

            'year_start.required' =>
                'Tahun awal wajib diisi.',

            'year_end.required' =>
                'Tahun akhir wajib diisi.',

            'year_end.gt' =>
                'Tahun akhir harus lebih besar dari tahun awal.',

            'registration_close.after_or_equal' =>
                'Tanggal penutupan tidak boleh sebelum tanggal pembukaan.',

            'status.required' =>
                'Status periode wajib dipilih.',

            'status.in' =>
                'Status periode tidak valid.',

            'number_prefix.required' =>
                'Prefix nomor pendaftaran wajib diisi.',

            'number_year.required' =>
                'Tahun nomor pendaftaran wajib diisi.',

            'number_digits.required' =>
                'Jumlah digit nomor wajib diisi.',

            'number_digits.min' =>
                'Jumlah digit nomor minimal 3.',

            'number_digits.max' =>
                'Jumlah digit nomor maksimal 8.',

            'default_reenroll_fee.required' =>
                'Biaya daftar ulang wajib diisi.',

            'default_reenroll_fee.integer' =>
                'Biaya daftar ulang harus berupa angka.',

            'default_reenroll_fee.min' =>
                'Biaya daftar ulang tidak boleh negatif.',

            'notes.max' =>
                'Catatan maksimal 2000 karakter.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $fee = preg_replace(
            '/\D+/',
            '',
            (string) $this->input(
                'default_reenroll_fee'
            )
        );

        $this->merge([
            'name' => $this->filled('name')
                ? trim(
                    (string) $this->input('name')
                )
                : null,

            'status' => $this->filled('status')
                ? strtoupper(
                    trim(
                        (string) $this->input('status')
                    )
                )
                : null,

            'principal_name' =>
                $this->filled('principal_name')
                    ? trim(
                        (string) $this->input(
                            'principal_name'
                        )
                    )
                    : null,

            'principal_nip' =>
                $this->filled('principal_nip')
                    ? trim(
                        (string) $this->input(
                            'principal_nip'
                        )
                    )
                    : null,

            'number_prefix' =>
                $this->filled('number_prefix')
                    ? strtoupper(
                        trim(
                            (string) $this->input(
                                'number_prefix'
                            )
                        )
                    )
                    : null,

            'default_reenroll_fee' =>
                $fee !== ''
                    ? (int) $fee
                    : 0,

            'is_active' =>
                $this->boolean('is_active'),

            'include_major_code' =>
                $this->boolean(
                    'include_major_code'
                ),

            'notes' =>
                $this->filled('notes')
                    ? trim(
                        (string) $this->input('notes')
                    )
                    : null,
        ]);
    }
}