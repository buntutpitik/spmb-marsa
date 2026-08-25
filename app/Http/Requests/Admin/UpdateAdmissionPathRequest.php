<?php

namespace App\Http\Requests\Admin;

use App\Models\AdmissionPath;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAdmissionPathRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        /** @var AdmissionPath|null $admissionPath */
        $admissionPath = $this->route('admissionPath');

        return [
            'period_id' => [
                'required',
                'integer',
                'exists:ppdb_periods,id',
            ],

            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('admission_paths', 'code')
                    ->where(
                        fn ($query) => $query->where(
                            'period_id',
                            $this->integer('period_id')
                        )
                    )
                    ->ignore($admissionPath?->id),
            ],

            'start_date' => [
                'nullable',
                'date',
            ],

            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],

            'sort_order' => [
                'required',
                'integer',
                'min:0',
                'max:9999',
            ],

            'description' => [
                'nullable',
                'string',
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

            'name.required' =>
                'Nama jalur pendaftaran wajib diisi.',

            'name.max' =>
                'Nama jalur pendaftaran maksimal 100 karakter.',

            'code.required' =>
                'Kode jalur pendaftaran wajib diisi.',

            'code.max' =>
                'Kode jalur pendaftaran maksimal 30 karakter.',

            'code.unique' =>
                'Kode jalur pendaftaran sudah digunakan pada periode ini.',

            'start_date.date' =>
                'Tanggal mulai tidak valid.',

            'end_date.date' =>
                'Tanggal selesai tidak valid.',

            'end_date.after_or_equal' =>
                'Tanggal selesai tidak boleh sebelum tanggal mulai.',

            'sort_order.required' =>
                'Urutan jalur pendaftaran wajib diisi.',

            'sort_order.integer' =>
                'Urutan jalur pendaftaran harus berupa angka.',

            'sort_order.min' =>
                'Urutan jalur pendaftaran tidak boleh negatif.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (
                $validator->errors()->has('period_id')
                || $validator->errors()->has('start_date')
                || $validator->errors()->has('end_date')
            ) {
                return;
            }

            if ($this->hasOverlappingActivePath()) {
                $validator->errors()->add(
                    'start_date',
                    'Rentang tanggal bertabrakan dengan jalur pendaftaran aktif lain pada periode ini.'
                );
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->filled('name')
                ? trim((string) $this->input('name'))
                : null,

            'code' => $this->filled('code')
                ? strtoupper(
                    trim((string) $this->input('code'))
                )
                : null,

            'start_date' => $this->filled('start_date')
                ? $this->input('start_date')
                : null,

            'end_date' => $this->filled('end_date')
                ? $this->input('end_date')
                : null,

            'description' => $this->filled('description')
                ? trim(
                    (string) $this->input('description')
                )
                : null,
        ]);
    }

    private function hasOverlappingActivePath(): bool
    {
        /** @var AdmissionPath|null $admissionPath */
        $admissionPath = $this->route('admissionPath');

        /*
         * Saat jalur master sedang nonaktif, mengedit data master
         * tidak menimbulkan konflik resolver karena jalur tersebut
         * belum ikut pemilihan otomatis.
         */
        if ($admissionPath && ! $admissionPath->is_active) {
            return false;
        }

        $startDate = $this->input('start_date');
        $endDate = $this->input('end_date');

        return AdmissionPath::query()
            ->where(
                'period_id',
                $this->integer('period_id')
            )
            ->where('is_active', true)
            ->when(
                $admissionPath,
                fn ($query) => $query->whereKeyNot(
                    $admissionPath->id
                )
            )
            ->where(function ($query) use ($endDate) {
                if ($endDate === null) {
                    return;
                }

                $query
                    ->whereNull('start_date')
                    ->orWhereDate(
                        'start_date',
                        '<=',
                        $endDate
                    );
            })
            ->where(function ($query) use ($startDate) {
                if ($startDate === null) {
                    return;
                }

                $query
                    ->whereNull('end_date')
                    ->orWhereDate(
                        'end_date',
                        '>=',
                        $startDate
                    );
            })
            ->exists();
    }
}