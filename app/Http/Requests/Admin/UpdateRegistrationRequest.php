<?php

namespace App\Http\Requests\Admin;

use App\Models\Registration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Http\Requests\Concerns\CopiesParentAddress;

class UpdateRegistrationRequest extends FormRequest
{
    use CopiesParentAddress;

    public function authorize(): bool
    {
        return auth()->check()
            && in_array(
                auth()->user()->role,
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
        /** @var Registration|null $registration */
        $registration = $this->route('registration');

        return [
            'nik' => [
                'required',
                'digits:16',
                Rule::unique('registrations', 'nik')
                    ->where(
                        fn ($query) => $query->where(
                            'period_id',
                            $registration?->period_id
                        )
                    )
                    ->ignore($registration?->id),
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

            /*
             * Pada tabel registrations, asal sekolah merupakan
             * snapshot nama sekolah. Form edit internal boleh
             * memperbaiki snapshot tersebut secara langsung.
             */
            'origin_school' => [
                'required',
                'string',
                'max:150',
            ],

            'hamlet' => [
                'nullable',
                'string',
                'max:100',
            ],

            'rt' => [
                'nullable',
                'string',
                'max:10',
            ],

            'rw' => [
                'nullable',
                'string',
                'max:10',
            ],

            'village' => [
                'nullable',
                'string',
                'max:100',
            ],

            'district' => [
                'nullable',
                'string',
                'max:100',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'province' => [
                'nullable',
                'string',
                'max:100',
            ],

            'postal_code' => [
                'nullable',
                'digits:5',
            ],

            'parent_hamlet' => ['nullable', 'string', 'max:100'],
            'parent_rt' => ['nullable', 'string', 'max:10'],
            'parent_rw' => ['nullable', 'string', 'max:10'],
            'parent_village' => ['nullable', 'string', 'max:100'],
            'parent_district' => ['nullable', 'string', 'max:100'],
            'parent_city' => ['nullable', 'string', 'max:100'],
            'parent_province' => ['nullable', 'string', 'max:100'],
            'parent_postal_code' => ['nullable', 'digits:5'],

            'same_parent_address' => ['sometimes', 'boolean'],

            'father_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'mother_name' => [
                'nullable',
                'string',
                'max:150',
            ],

            'father_job' => [
                'nullable',
                'string',
                'max:100',
            ],

            'mother_job' => [
                'nullable',
                'string',
                'max:100',
            ],

            'whatsapp' => [
                'required',
                'string',
                'max:30',
                'regex:/^(?:\+62|62|0|8)[0-9\s\-\(\)\.]{7,20}$/',
            ],

            'graduation_year' => [
                'sometimes',
                'nullable',
                'integer',
                'between:2000,2100',
            ],

            'graduation_score' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'admission_path_id' => [
                'required',
                'integer',
                'exists:admission_paths,id',
            ],

            'major_id' => [
                'required',
                'integer',
                'exists:majors,id',
            ],

            'referral_source_id' => [
                'sometimes',
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($registration) {
                    if ($value === null) {
                        return;
                    }

                    if (
                        $registration instanceof Registration
                        && (int) $value === (int) $registration->referral_source_id
                        && $registration->referral_source_id !== null
                    ) {
                        return;
                    }

                    $active = \App\Models\ReferralSource::query()
                        ->whereKey($value)
                        ->where('is_active', true)
                        ->exists();

                    if (! $active) {
                        $fail('Kategori Pembawa harus berasal dari master aktif.');
                    }
                },
            ],

            'referrer_class' => [
                Rule::requiredIf(function () use ($registration) {
                    $sourceId = $this->exists('referral_source_id')
                        ? $this->input('referral_source_id')
                        : $registration?->referral_source_id;

                    return \App\Models\ReferralSource::query()
                        ->whereKey($sourceId)
                        ->where('code', 'ACTIVE_STUDENT')
                        ->exists()
                        && (
                            $this->exists('referrer_class')
                            || (
                                $this->exists('referral_source_id')
                                && (
                                    (int) $sourceId !== (int) $registration?->referral_source_id
                                    || ! $registration?->referrer_class
                                )
                            )
                        );
                }),
                Rule::prohibitedIf(function () use ($registration) {
                    $sourceId = $this->exists('referral_source_id')
                        ? $this->input('referral_source_id')
                        : $registration?->referral_source_id;

                    return ! \App\Models\ReferralSource::query()
                        ->whereKey($sourceId)
                        ->where('code', 'ACTIVE_STUDENT')
                        ->exists();
                }),
                'nullable',
                'string',
                'max:100',
            ],

            'referrer_registration_id' => [
                Rule::requiredIf(function () use ($registration) {
                    $sourceId = $this->exists('referral_source_id')
                        ? $this->input('referral_source_id')
                        : $registration?->referral_source_id;

                    $isProspectiveStudent = \App\Models\ReferralSource::query()
                        ->whereKey($sourceId)
                        ->where('code', 'PROSPECTIVE_STUDENT')
                        ->exists();

                    return $isProspectiveStudent
                        && (
                            $this->exists('referrer_registration_id')
                            || ! $registration?->referrer_registration_id
                            || (
                                $this->exists('referral_source_id')
                                && (int) $sourceId !== (int) $registration?->referral_source_id
                            )
                        );
                }),
                Rule::prohibitedIf(function () use ($registration) {
                    $sourceId = $this->exists('referral_source_id')
                        ? $this->input('referral_source_id')
                        : $registration?->referral_source_id;

                    return ! \App\Models\ReferralSource::query()
                        ->whereKey($sourceId)
                        ->where('code', 'PROSPECTIVE_STUDENT')
                        ->exists();
                }),
                'nullable',
                'integer',
                Rule::exists('registrations', 'id')
                    ->where('period_id', $registration?->period_id),
                Rule::notIn([$registration?->id]),
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
                function ($attribute, $value, $fail) {
                    $registration = $this->route('registration');

                    // Nilai historis milik pendaftaran ini boleh dipertahankan.
                    if (
                        $registration instanceof \App\Models\Registration
                        && $value === $registration->referrer_source
                    ) {
                        return;
                    }

                    // Nilai baru harus berasal dari master aktif.
                    $exists = \App\Models\ReferralSource::query()
                        ->where('name', $value)
                        ->where('is_active', true)
                        ->exists();

                    if (! $exists) {
                        $fail('Asal Pembawa harus dipilih dari master yang aktif.');
                    }
                },
            ],

            'referrer_notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:2000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'relief_options' => [
                'nullable',
                'array',
            ],

            'relief_options.*' => [
                'integer',
                'distinct',
                'exists:relief_options,id',
            ],

            'special_programs' => [
                'nullable',
                'array',
            ],

            'special_programs.*' => [
                'integer',
                'distinct',
                'exists:special_programs,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nik.required' =>
                'NIK wajib diisi.',

            'nik.digits' =>
                'NIK harus terdiri dari 16 digit.',

            'nik.unique' =>
                'NIK sudah terdaftar pada periode SPMB ini.',

            'nisn.digits_between' =>
                'NISN harus berupa angka yang valid.',

            'full_name.required' =>
                'Nama lengkap wajib diisi.',

            'birth_date.before' =>
                'Tanggal lahir harus sebelum hari ini.',

            'gender.in' =>
                'Jenis kelamin tidak valid.',

            'origin_school.required' =>
                'Asal sekolah wajib diisi.',

            'postal_code.digits' =>
                'Kode pos harus terdiri dari 5 digit angka.',

            'whatsapp.required' =>
                'Nomor WhatsApp wajib diisi.',

            'whatsapp.regex' =>
                'Format nomor WhatsApp tidak valid.',

            'graduation_score.numeric' =>
                'Nilai kelulusan harus berupa angka.',

            'graduation_score.min' =>
                'Nilai kelulusan minimal 0.',

            'graduation_score.max' =>
                'Nilai kelulusan maksimal 100.',

            'admission_path_id.required' =>
                'Jalur pendaftaran wajib dipilih.',

            'major_id.required' =>
                'Jurusan wajib dipilih.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $uppercaseFields = [
            'full_name',
            'birth_place',
            'religion',
            'origin_school',
            'hamlet',
            'village',
            'district',
            'city',
            'province',
            'father_name',
            'mother_name',
        ];

        $prepared = [
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

            'whatsapp' => trim(
                (string) $this->input('whatsapp')
            ),
        ];

        foreach ($uppercaseFields as $field) {
            $prepared[$field] = $this->filled($field)
                ? mb_strtoupper(
                    trim((string) $this->input($field))
                )
                : null;
        }

        foreach (
            [
                'rt',
                'rw',
                'postal_code',
                'father_job',
                'mother_job',
                'referrer_name',
                'referrer_source',
                'notes',
            ] as $field
        ) {
            $prepared[$field] = $this->filled($field)
                ? trim((string) $this->input($field))
                : null;
        }

        $this->merge($prepared);
        $this->copyParentAddressWhenRequested();
    }
}