<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Services\PeriodContext;

class CreateRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array(
            $this->user()?->role,
            [
                'SUPERADMIN',
                'ADMIN',
                'PANITIA',
                'BENDAHARA',
            ],
            true
        );
    }

    public function rules(): array
    {
        $period = app(PeriodContext::class)
            ->resolveActivePeriod();

        $periodId = $period?->id;

        return [
            'nik' => [
                'required',
                'digits:16',
                Rule::unique('registrations', 'nik')
                    ->where(
                        fn ($query) => $query->where(
                            'period_id',
                            $periodId
                        )
                    ),
            ],

            'nisn' => [
                'nullable',
                'digits_between:8,20',
            ],

            'full_name' => [
                'required',
                'string',
                'max:150',
            ],

            'birth_place' => [
                'nullable',
                'string',
                'max:100',
            ],

            'birth_date' => [
                'nullable',
                'date',
                'before:today',
            ],

            'gender' => [
                'nullable',
                Rule::in(['L', 'P']),
            ],

            'religion' => [
                'nullable',
                'string',
                'max:50',
            ],

            'origin_school_id' => [
                'required',

                function (
                    string $attribute,
                    mixed $value,
                    \Closure $fail
                ): void {
                    /*
                    * "OTHER" memang pilihan valid.
                    */
                    if ($value === 'OTHER') {
                        return;
                    }

                    if (! ctype_digit((string) $value)) {
                        $fail(
                            'Asal sekolah yang dipilih tidak valid.'
                        );

                        return;
                    }

                    $exists = \Illuminate\Support\Facades\DB::table(
                        'origin_schools'
                    )
                        ->where('id', (int) $value)
                        ->where('is_active', true)
                        ->exists();

                    if (! $exists) {
                        $fail(
                            'Asal sekolah tidak tersedia atau tidak aktif.'
                        );
                    }
                },
            ],

            'origin_school_other' => [
                'nullable',
                'required_if:origin_school_id,OTHER',
                'string',
                'max:150',
            ],

            'hamlet' => ['nullable', 'string', 'max:100'],
            'rt' => ['nullable', 'string', 'max:10'],
            'rw' => ['nullable', 'string', 'max:10'],
            'village' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],

            'postal_code' => [
                'nullable',
                'digits:5',
            ],

            'father_name' => ['nullable', 'string', 'max:150'],
            'mother_name' => ['nullable', 'string', 'max:150'],
            'father_job' => ['nullable', 'string', 'max:100'],
            'mother_job' => ['nullable', 'string', 'max:100'],

            'whatsapp' => [
                'required',
                'string',
                'max:30',
                'regex:/^(?:\+62|62|0|8)[0-9\s\-\(\)\.]{7,20}$/',
            ],

            'graduation_score' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'major_id' => [
                'required',
                'integer',

                /*
                * Jurusan harus benar-benar tersedia dan aktif
                * pada pivot periode aktif.
                */
                Rule::exists(
                    'period_majors',
                    'major_id'
                )->where(
                    fn ($query) => $query
                        ->where('period_id', $periodId)
                        ->where('is_active', true)
                ),
            ],

            'referrer_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'referrer_source' => [
                'nullable',
                'string',
                'max:150',
            ],

            'relief_options' => [
                'nullable',
                'array',
            ],

            'relief_options.*' => [
                'integer',
                'distinct',

                Rule::exists(
                    'period_relief_options',
                    'relief_option_id'
                )->where(
                    fn ($query) => $query
                        ->where(
                            'ppdb_period_id',
                            $periodId
                        )
                        ->where('is_active', true)
                ),

                Rule::exists(
                    'relief_options',
                    'id'
                )->where(
                    fn ($query) => $query
                        ->where('is_active', true)
                ),
            ],

            'special_programs' => [
                'nullable',
                'array',
            ],

            'special_programs.*' => [
                'integer',
                'distinct',

                Rule::exists(
                    'period_special_programs',
                    'special_program_id'
                )->where(
                    fn ($query) => $query
                        ->where(
                            'ppdb_period_id',
                            $periodId
                        )
                        ->where('is_active', true)
                ),

                Rule::exists(
                    'special_programs',
                    'id'
                )->where(
                    fn ($query) => $query
                        ->where('is_active', true)
                ),
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nik' => preg_replace(
                '/\D+/',
                '',
                (string) $this->input('nik')
            ),

            'nisn' => $this->filled('nisn')
                ? preg_replace(
                    '/\D+/',
                    '',
                    (string) $this->input('nisn')
                )
                : null,

            'full_name' => $this->filled('full_name')
                ? mb_strtoupper(
                    trim((string) $this->input('full_name'))
                )
                : null,

            'birth_place' => $this->filled('birth_place')
                ? mb_strtoupper(
                    trim((string) $this->input('birth_place'))
                )
                : null,

            'religion' => $this->filled('religion')
                ? mb_strtoupper(
                    trim((string) $this->input('religion'))
                )
                : null,

            'origin_school_other' =>
                $this->filled('origin_school_other')
                    ? mb_strtoupper(
                        trim(
                            (string) $this->input(
                                'origin_school_other'
                            )
                        )
                    )
                    : null,

            'whatsapp' => trim(
                (string) $this->input('whatsapp')
            ),
        ]);
    }
}