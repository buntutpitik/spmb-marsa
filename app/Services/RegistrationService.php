<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Major;
use App\Models\OriginSchool;
use App\Models\PeriodMajor;
use App\Models\Registration;
use App\Models\RegistrationStatusHistory;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RegistrationService
{
    public function __construct(
        protected RegistrationNumberService $numberService,
        protected WhatsappNotificationService $notificationService,
        protected AdmissionPathResolver $admissionPathResolver,
        protected PeriodContext $periodContext
    ) {
    }

    public function create(
        array $data,
        ?User $creator = null,
        ?string $whatsappMessage = null,
        array $auditContext = []
    ): Registration {
        return DB::transaction(function () use (
            $data,
            $creator,
            $whatsappMessage,
            $auditContext
        ) {
            /*
             * ---------------------------------------------------------
             * 1. Periode dan jurusan.
             * ---------------------------------------------------------
             */
            $period = $this->periodContext
            ->resolveActivePeriod(
                (int) $data['period_id']
            );

            if (! $period) {
                throw new InvalidArgumentException(
                    'Periode SPMB tidak aktif atau tidak dibuka.'
                );
            }

            $major = Major::query()
                ->findOrFail($data['major_id']);

            /*
             * ---------------------------------------------------------
             * 2. Jalur pendaftaran.
             * ---------------------------------------------------------
             *
             * PUBLIC:
             * Jalur ditentukan otomatis berdasarkan jadwal yang
             * sedang berlaku melalui AdmissionPathResolver.
             *
             * ADMIN:
             * Jalur juga ditentukan otomatis. Jalur yang sedang
             * berlaku diprioritaskan. Jika belum ada jalur yang
             * berlaku, gunakan jalur aktif terdekat yang akan datang.
             *
             * admission_path_id dari client tidak dipercaya.
             */
            if ($creator === null) {
                $admissionPath = $this->admissionPathResolver->resolve(
                    $period,
                    now()
                );
            } else {
                $admissionPath = $this->admissionPathResolver
                    ->resolveForAdmin(
                        $period,
                        now()
                    );
            }
            /*
             * ---------------------------------------------------------
             * 3. Validasi jurusan.
             * ---------------------------------------------------------
             */
            if ($major->school_id !== $period->school_id) {
                throw new InvalidArgumentException(
                    'Jurusan tidak berasal dari sekolah yang sama dengan periode SPMB.'
                );
            }

            $periodMajorExists = PeriodMajor::query()
                ->where('period_id', $period->id)
                ->where('major_id', $major->id)
                ->where('is_active', true)
                ->exists();

            if (! $periodMajorExists) {
                throw new InvalidArgumentException(
                    'Jurusan tidak tersedia atau tidak aktif pada periode SPMB yang dipilih.'
                );
            }

            /*
             * ---------------------------------------------------------
             * 4. Asal Sekolah.
             * ---------------------------------------------------------
             *
             * Request tidak boleh mengirim origin_school secara bebas.
             *
             * Pilihan:
             *
             * - ID master origin_schools
             * - OTHER + origin_school_other
             *
             * registrations.origin_school tetap menyimpan snapshot
             * nama final supaya histori pendaftaran tetap aman jika
             * master sekolah diubah di kemudian hari.
             */
            $originSchoolSelection = $data['origin_school_id'] ?? null;

            if ($originSchoolSelection === 'OTHER') {
                $originSchoolName = mb_strtoupper(
                    trim(
                        (string) (
                            $data['origin_school_other']
                            ?? ''
                        )
                    )
                );

                if ($originSchoolName === '') {
                    throw new InvalidArgumentException(
                        'Nama asal sekolah wajib diisi.'
                    );
                }

                if (mb_strlen($originSchoolName) > 150) {
                    throw new InvalidArgumentException(
                        'Nama asal sekolah maksimal 150 karakter.'
                    );
                }
            } else {
                if (
                    $originSchoolSelection === null
                    || $originSchoolSelection === ''
                    || ! ctype_digit(
                        (string) $originSchoolSelection
                    )
                ) {
                    throw new InvalidArgumentException(
                        'Asal sekolah tidak valid.'
                    );
                }

                $originSchool = OriginSchool::query()
                    ->whereKey(
                        (int) $originSchoolSelection
                    )
                    ->where('is_active', true)
                    ->first();

                if (! $originSchool) {
                    throw new InvalidArgumentException(
                        'Asal sekolah tidak tersedia atau tidak aktif.'
                    );
                }

                /*
                 * Snapshot mengikuti nama master.
                 */
                $originSchoolName = $originSchool->name;
            }

            /*
             * ---------------------------------------------------------
             * 5. Keringanan / Prestasi.
             * ---------------------------------------------------------
             */
            $reliefOptionIds = collect(
                $data['relief_options'] ?? []
            )
                ->map(
                    fn ($id) => (int) $id
                )
                ->filter(
                    fn ($id) => $id > 0
                )
                ->unique()
                ->values();

            if ($reliefOptionIds->isNotEmpty()) {
                $validReliefOptionIds = $period
                    ->reliefOptions()
                    ->where(
                        'relief_options.is_active',
                        true
                    )
                    ->wherePivot(
                        'is_active',
                        true
                    )
                    ->whereIn(
                        'relief_options.id',
                        $reliefOptionIds->all()
                    )
                    ->pluck(
                        'relief_options.id'
                    )
                    ->map(
                        fn ($id) => (int) $id
                    )
                    ->unique()
                    ->values();

                if (
                    $validReliefOptionIds->count()
                    !== $reliefOptionIds->count()
                ) {
                    throw new InvalidArgumentException(
                        'Salah satu pilihan keringanan tidak tersedia pada periode SPMB ini.'
                    );
                }
            }

            /*
             * ---------------------------------------------------------
             * 6. Program Khusus.
             * ---------------------------------------------------------
             */
            $specialProgramIds = collect(
                $data['special_programs'] ?? []
            )
                ->map(
                    fn ($id) => (int) $id
                )
                ->filter(
                    fn ($id) => $id > 0
                )
                ->unique()
                ->values();

            if ($specialProgramIds->isNotEmpty()) {
                $validSpecialProgramIds = $period
                    ->specialPrograms()
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
                        $specialProgramIds->all()
                    )
                    ->pluck(
                        'special_programs.id'
                    )
                    ->map(
                        fn ($id) => (int) $id
                    )
                    ->unique()
                    ->values();

                if (
                    $validSpecialProgramIds->count()
                    !== $specialProgramIds->count()
                ) {
                    throw new InvalidArgumentException(
                        'Salah satu Program Khusus tidak tersedia pada periode SPMB ini.'
                    );
                }
            }

            /*
             * ---------------------------------------------------------
             * 7. Nomor pendaftaran.
             * ---------------------------------------------------------
             */
            $registrationNumber = $this->numberService->generate(
                $period,
                $major
            );

            /*
             * ---------------------------------------------------------
             * 8. Public token.
             * ---------------------------------------------------------
             *
             * ULID:
             * - 26 karakter
             * - unik
             * - tidak menggunakan nomor pendaftaran
             */
            $publicToken = (string) Str::ulid();

            /*
             * ---------------------------------------------------------
             * 9. Biodata yang boleh disimpan.
             * ---------------------------------------------------------
             *
             * origin_school_id dan origin_school_other TIDAK ikut
             * mass assignment karena hanya input bantu.
             *
             * origin_school akan diisi sendiri oleh service.
             */
            $payload = Arr::only($data, [
                'nik',
                'nisn',
                'full_name',
                'birth_place',
                'birth_date',
                'gender',
                'religion',

                'hamlet',
                'rt',
                'rw',
                'village',
                'district',
                'city',
                'province',
                'postal_code',

                'father_name',
                'mother_name',
                'father_job',
                'mother_job',

                'whatsapp',

                'graduation_score',

                'referrer_name',
                'referrer_source',

                'notes',
            ]);

            /*
             * Snapshot asal sekolah final.
             */
            $payload['origin_school'] = $originSchoolName;

            /*
             * ---------------------------------------------------------
             * 10. Nilai yang dikendalikan sistem.
             * ---------------------------------------------------------
             */
            $payload['period_id'] = $period->id;

            /*
             * Jalur hasil resolver.
             */
            $payload['admission_path_id'] = $admissionPath->id;

            $payload['major_id'] = $major->id;

            $payload['registration_number'] =
                $registrationNumber;

            $payload['data_source'] = $creator
                ? 'ADMIN'
                : 'PUBLIC';

            $payload['status'] = 'REGISTERED';

            $payload['created_by'] =
                $creator?->id;

            $payload['registered_at'] =
                now();

            /*
             * ---------------------------------------------------------
             * 11. Simpan pendaftaran.
             * ---------------------------------------------------------
             */
            $registration = Registration::create(
                $payload
            );

            /*
             * public_token tidak perlu dimasukkan ke $fillable.
             */
            $registration->forceFill([
                'public_token' => $publicToken,
            ])->save();

            /*
             * ---------------------------------------------------------
             * 12. Pivot Keringanan / Prestasi.
             * ---------------------------------------------------------
             */
            $registration->reliefOptions()->sync(
                $reliefOptionIds->all()
            );

            /*
             * ---------------------------------------------------------
             * 13. Pivot Program Khusus.
             * ---------------------------------------------------------
             */
            $registration->specialPrograms()->sync(
                $specialProgramIds->all()
            );

            /*
             * ---------------------------------------------------------
             * 14. Status history awal.
             * ---------------------------------------------------------
             */
            RegistrationStatusHistory::create([
                'registration_id' =>
                    $registration->id,

                'from_status' =>
                    null,

                'to_status' =>
                    'REGISTERED',

                'changed_by' =>
                    $creator?->id,

                'changed_at' =>
                    now(),

                'notes' =>
                    'Pendaftaran dibuat.',
            ]);

            /*
             * ---------------------------------------------------------
             * 15. Activity log.
             * ---------------------------------------------------------
             */
            ActivityLog::create([
                'user_id' =>
                    $creator?->id,

                'registration_id' =>
                    $registration->id,

                'action' =>
                    'CREATE_REGISTRATION',

                'description' =>
                    'Pendaftaran baru dibuat.',

                'metadata' => [
                    'registration_number' =>
                        $registrationNumber,

                    'data_source' =>
                        $payload['data_source'],

                    'period_id' =>
                        $period->id,

                    'admission_path_id' =>
                        $admissionPath->id,

                    'admission_path_code' =>
                        $admissionPath->code,

                    'major_id' =>
                        $major->id,

                    /*
                     * Snapshot informasi asal sekolah juga masuk
                     * metadata audit.
                     */
                    'origin_school' =>
                        $originSchoolName,

                    'origin_school_master_id' =>
                        $originSchoolSelection === 'OTHER'
                            ? null
                            : (int) $originSchoolSelection,

                    'origin_school_is_other' =>
                        $originSchoolSelection === 'OTHER',

                    'relief_option_ids' =>
                        $reliefOptionIds->all(),

                    'special_program_ids' =>
                        $specialProgramIds->all(),
                ],

                'ip_address' =>
                    $auditContext['ip_address'] ?? null,

                'user_agent' =>
                    $auditContext['user_agent'] ?? null,
            ]);

            /*
             * ---------------------------------------------------------
             * 16. WhatsApp notification.
             * ---------------------------------------------------------
             *
             * Notification service sendiri yang menentukan
             * provider / queue / enabled state.
             */
            if (
                $whatsappMessage !== null
                && trim($whatsappMessage) !== ''
            ) {
                $this->notificationService
                    ->registrationSuccess(
                        $registration
                    );
            }

            /*
             * ---------------------------------------------------------
             * 17. Return lengkap.
             * ---------------------------------------------------------
             */
            return $registration->load([
                'period',
                'admissionPath',
                'major',
                'reliefOptions',
                'specialPrograms',
                'statusHistories',
                'activityLogs',
                'whatsappLogs',
            ]);
        });
    }
}
