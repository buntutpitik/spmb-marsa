<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePeriodMajorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'period_id' => [
                'required',
                'integer',
                'exists:ppdb_periods,id',
            ],

            'is_active' => [
                'nullable',
                'boolean',
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
            'period_id.required' =>
                'Periode wajib dipilih.',

            'period_id.exists' =>
                'Periode tidak valid.',

            'is_active.boolean' =>
                'Status jurusan pada periode tidak valid.',

            'quota.integer' =>
                'Kuota harus berupa angka.',

            'quota.min' =>
                'Kuota tidak boleh negatif.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' =>
                $this->boolean('is_active'),

            'quota' => $this->filled('quota')
                ? $this->input('quota')
                : null,
        ]);
    }
}