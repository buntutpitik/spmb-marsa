<?php

namespace App\Http\Requests;

use App\Models\OriginSchool;
use App\Services\PeriodContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePublicRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_id' => [
                'required',
                'integer',
                'exists:ppdb_periods,id',
            ],

            /*
             * Jalur ditentukan otomatis berdasarkan tanggal.
             */

            'major_id' => [
                'required',
                'integer',
                'exists:majors,id',
            ],

            'nik' => [
                'required',
                'digits:16',
                Rule::unique('registrations', 'nik')
                    ->where(
                        fn ($query) => $query->where(
                            'period_id',
                            $this->input('period_id')
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
                Rule::in([
                    'L',
                    'P',
                ]),
            ],

            'religion' => [
                'nullable',
                'string',
                'max:50',
            ],

            /*
             * ---------------------------------------------------------
             * Asal Sekolah
             * ---------------------------------------------------------
             *
             * origin_school_id berisi:
             *
             * - ID record origin_schools
             * - atau string OTHER bila sekolah tidak ada di master.
             *
             * registrations.origin_school nantinya tetap menyimpan
             * snapshot nama sekolah final.
             */

            'origin_school_id' => [
                'required',
                function (
                    string $attribute,
                    mixed $value,
                    \Closure $fail
                ): void {
                    if ($value === 'OTHER') {
                        return;
                    }

                    if (! ctype_digit((string) $value)) {
                        $fail('Asal sekolah tidak valid.');
                    }
                },
            ],

            'origin_school_other' => [
                'nullable',
                'string',
                'max:150',
                Rule::requiredIf(
                    fn () =>
                        $this->input('origin_school_id') === 'OTHER'
                ),
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

            /*
             * Program Khusus tetap boleh dipilih oleh calon siswa
             * melalui formulir publik.
             */

            'special_programs' => [
                'nullable',
                'array',
            ],

            'special_programs.*' => [
                'integer',
                'distinct',
                'exists:special_programs,id',
            ],

            /*
             * relief_options, referrer_name, dan referrer_source
             * sengaja TIDAK divalidasi pada request publik.
             *
             * Ketiganya merupakan data internal yang akan dikelola
             * oleh petugas/admin setelah calon siswa datang ke sekolah.
             *
             * Karena controller menggunakan $request->validated(),
             * field tersebut tidak akan diteruskan ke RegistrationService
             * walaupun disisipkan secara manual melalui request publik.
             */

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
            'period_id.required' =>
                'Periode SPMB wajib dipilih.',

            'period_id.exists' =>
                'Periode SPMB tidak valid.',

            'major_id.required' =>
                'Jurusan wajib dipilih.',

            'major_id.exists' =>
                'Jurusan tidak valid.',

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

            'origin_school_id.required' =>
                'Asal sekolah wajib dipilih.',

            'origin_school_other.required' =>
                'Nama asal sekolah wajib diisi jika memilih Lainnya.',

            'origin_school_other.max' =>
                'Nama asal sekolah maksimal 150 karakter.',

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

            'special_programs.array' =>
                'Pilihan Program Khusus tidak valid.',

            'special_programs.*.integer' =>
                'Pilihan Program Khusus tidak valid.',

            'special_programs.*.distinct' =>
                'Pilihan Program Khusus tidak boleh duplikat.',

            'special_programs.*.exists' =>
                'Pilihan Program Khusus tidak ditemukan.',
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
                    trim(
                        (string) $this->input('full_name')
                    )
                )
                : null,

            'birth_place' => $this->filled('birth_place')
                ? mb_strtoupper(
                    trim(
                        (string) $this->input('birth_place')
                    )
                )
                : null,

            'religion' => $this->filled('religion')
                ? mb_strtoupper(
                    trim(
                        (string) $this->input('religion')
                    )
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

    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (Validator $validator): void {

                /*
                 * ---------------------------------------------------------
                 * Validasi Asal Sekolah
                 * ---------------------------------------------------------
                 */

                $originSchoolId = $this->input(
                    'origin_school_id'
                );

                if (
                    $originSchoolId !== null
                    && $originSchoolId !== ''
                    && $originSchoolId !== 'OTHER'
                    && ctype_digit(
                        (string) $originSchoolId
                    )
                ) {
                    $originSchoolExists =
                        OriginSchool::query()
                            ->whereKey(
                                (int) $originSchoolId
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->exists();

                    if (! $originSchoolExists) {
                        $validator->errors()->add(
                            'origin_school_id',
                            'Asal sekolah tidak tersedia atau sudah tidak aktif.'
                        );
                    }
                }

                /*
                 * ---------------------------------------------------------
                 * Validasi periode.
                 * ---------------------------------------------------------
                 */

                if (
                    $validator->errors()->has(
                        'period_id'
                    )
                    || ! $this->filled('period_id')
                ) {
                    return;
                }

                $period = app(PeriodContext::class)
                    ->resolveActivePeriod(
                        $this->integer('period_id')
                    );

                if (! $period) {
                    $validator->errors()->add(
                        'period_id',
                        'Periode SPMB tidak aktif atau tidak dibuka.'
                    );

                    return;
                }

                /*
                 * ---------------------------------------------------------
                 * Validasi Program Khusus.
                 * ---------------------------------------------------------
                 *
                 * ID harus:
                 * - tersedia pada periode,
                 * - aktif di master,
                 * - aktif di pivot periode.
                 */

                $selectedProgramIds = collect(
                    $this->input(
                        'special_programs',
                        []
                    )
                )
                    ->map(
                        fn ($id) => (int) $id
                    )
                    ->unique()
                    ->values();

                if (
                    $selectedProgramIds
                        ->isNotEmpty()
                ) {
                    $validProgramIds =
                        $period->specialPrograms()
                            ->where(
                                'special_programs.is_active',
                                true
                            )
                            ->wherePivot(
                                'is_active',
                                true
                            )
                            ->whereIn(
                                'special_programs.id',
                                $selectedProgramIds
                                    ->all()
                            )
                            ->pluck(
                                'special_programs.id'
                            )
                            ->map(
                                fn ($id) =>
                                    (int) $id
                            )
                            ->unique()
                            ->values();

                    if (
                        $validProgramIds->count()
                        !==
                        $selectedProgramIds->count()
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                'special_programs',
                                'Salah satu Program Khusus tidak tersedia pada periode SPMB ini.'
                            );
                    }
                }
            }
        );
    }
}