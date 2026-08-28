<?php

namespace App\Http\Requests\Admin;

use App\Models\Registration;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRegistrationRequest extends FormRequest
{
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
    }
}