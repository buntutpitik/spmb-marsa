<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMajorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'school_id' => [
                'required',
                'integer',
                'exists:schools,id',
            ],

            'period_id' => [
                'required',
                'integer',
                'exists:ppdb_periods,id',
            ],

            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('majors', 'code')
                    ->where(
                        fn ($query) => $query->where(
                            'school_id',
                            $this->integer('school_id')
                        )
                    ),
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'short_name' => [
                'nullable',
                'string',
                'max:50',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:9999',
            ],

            'quota' => [
                'nullable',
                'integer',
                'min:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'school_id.required' =>
                'Sekolah wajib dipilih.',

            'school_id.exists' =>
                'Sekolah tidak valid.',

            'period_id.required' =>
                'Periode wajib dipilih.',

            'period_id.exists' =>
                'Periode tidak valid.',

            'code.required' =>
                'Kode jurusan wajib diisi.',

            'code.max' =>
                'Kode jurusan maksimal 20 karakter.',

            'code.unique' =>
                'Kode jurusan sudah digunakan pada sekolah ini.',

            'name.required' =>
                'Nama jurusan wajib diisi.',

            'name.max' =>
                'Nama jurusan maksimal 150 karakter.',

            'short_name.max' =>
                'Nama singkat maksimal 50 karakter.',

            'sort_order.required' =>
                'Urutan jurusan wajib diisi.',

            'sort_order.integer' =>
                'Urutan jurusan harus berupa angka.',

            'sort_order.min' =>
                'Urutan jurusan tidak boleh negatif.',

            'quota.integer' =>
                'Kuota harus berupa angka.',

            'quota.min' =>
                'Kuota tidak boleh negatif.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => $this->filled('code')
                ? strtoupper(
                    trim((string) $this->input('code'))
                )
                : null,

            'name' => $this->filled('name')
                ? trim((string) $this->input('name'))
                : null,

            'short_name' => $this->filled('short_name')
                ? strtoupper(
                    trim(
                        (string) $this->input('short_name')
                    )
                )
                : null,

            'description' => $this->filled('description')
                ? trim(
                    (string) $this->input('description')
                )
                : null,

            'quota' => $this->filled('quota')
                ? $this->input('quota')
                : null,
        ]);
    }
}