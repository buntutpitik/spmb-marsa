<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use App\Models\WhatsappLog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminWhatsappLogTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_access_whatsapp_log_page(): void
    {
        $this->get(
            route('admin.whatsapp-logs.index')
        )->assertRedirect(
            route('login')
        );
    }

    public function test_admin_can_access_whatsapp_log_page(): void
    {
        [$user, $period] = $this->makeFixture();

        $this->actingAs($user)
            ->get(
                route('admin.whatsapp-logs.index', [
                    'period_id' => $period->id,
                ])
            )
            ->assertOk()
            ->assertSee('Riwayat WhatsApp')
            ->assertSee('Log Pengiriman');
    }

    public function test_whatsapp_log_only_uses_selected_period(): void
    {
        [
            $user,
            $period,
            $path,
            $major,
        ] = $this->makeFixture();

        $registration = $this->makeRegistration(
            $period,
            $path,
            $major,
            'PENDAFTAR PERIODE AKTIF'
        );

        $this->makeWhatsappLog(
            $registration,
            'SUCCESS',
            'registration_success',
            '081211110001',
            'wamid.active'
        );

        /*
         * ---------------------------------------------------------
         * Periode kedua.
         * ---------------------------------------------------------
         */
        $otherSchool = School::create([
            'name' => 'SMK WHATSAPP OTHER TEST',
        ]);

        $otherPeriod = PpdbPeriod::create([
            'school_id' => $otherSchool->id,
            'name' => '2028/2029',
            'year_start' => 2028,
            'year_end' => 2029,
            'status' => 'OPEN',
            'is_active' => false,
            'number_year' => 2028,
        ]);

        $otherPath = AdmissionPath::create([
            'period_id' => $otherPeriod->id,
            'name' => 'UMUM',
            'code' => 'UMUM',
            'is_active' => true,
        ]);

        $otherMajor = Major::create([
            'school_id' => $otherSchool->id,
            'code' => 'OTH',
            'name' => 'JURUSAN OTHER',
            'is_active' => true,
        ]);

        $otherRegistration = $this->makeRegistration(
            $otherPeriod,
            $otherPath,
            $otherMajor,
            'PENDAFTAR PERIODE LAIN'
        );

        $this->makeWhatsappLog(
            $otherRegistration,
            'SUCCESS',
            'registration_success',
            '081211119999',
            'wamid.other'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('admin.whatsapp-logs.index', [
                    'period_id' => $period->id,
                ])
            );

        $response
            ->assertOk()
            ->assertSee('PENDAFTAR PERIODE AKTIF')
            ->assertDontSee('PENDAFTAR PERIODE LAIN')
            ->assertSee('081211110001')
            ->assertDontSee('081211119999');
    }

    public function test_whatsapp_log_can_filter_status(): void
    {
        [
            $user,
            $period,
            $path,
            $major,
        ] = $this->makeFixture();

        $successRegistration = $this->makeRegistration(
            $period,
            $path,
            $major,
            'CALON WA BERHASIL'
        );

        $failedRegistration = $this->makeRegistration(
            $period,
            $path,
            $major,
            'CALON WA GAGAL'
        );

        $this->makeWhatsappLog(
            $successRegistration,
            'SUCCESS',
            'registration_success',
            '081222220001',
            'wamid.success'
        );

        $this->makeWhatsappLog(
            $failedRegistration,
            'FAILED',
            'registration_success',
            '081222220002',
            null
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('admin.whatsapp-logs.index', [
                    'period_id' => $period->id,
                    'status' => 'FAILED',
                ])
            );

        $response
            ->assertOk()
            ->assertSee('CALON WA GAGAL')
            ->assertDontSee('CALON WA BERHASIL');
    }

    public function test_whatsapp_log_can_filter_message_type(): void
    {
        [
            $user,
            $period,
            $path,
            $major,
        ] = $this->makeFixture();

        $registrationSuccess = $this->makeRegistration(
            $period,
            $path,
            $major,
            'CALON REGISTRATION MESSAGE'
        );

        $registrationAccepted = $this->makeRegistration(
            $period,
            $path,
            $major,
            'CALON ACCEPTED MESSAGE'
        );

        $this->makeWhatsappLog(
            $registrationSuccess,
            'SUCCESS',
            'registration_success',
            '081233330001',
            'wamid.registration'
        );

        $this->makeWhatsappLog(
            $registrationAccepted,
            'SUCCESS',
            'registration_accepted',
            '081233330002',
            'wamid.accepted'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('admin.whatsapp-logs.index', [
                    'period_id' => $period->id,
                    'message_type' => 'registration_accepted',
                ])
            );

        $response
            ->assertOk()
            ->assertSee('CALON ACCEPTED MESSAGE')
            ->assertDontSee('CALON REGISTRATION MESSAGE');
    }

    public function test_whatsapp_log_can_search_registration_name(): void
    {
        [
            $user,
            $period,
            $path,
            $major,
        ] = $this->makeFixture();

        $target = $this->makeRegistration(
            $period,
            $path,
            $major,
            'BUDI WHATSAPP TARGET'
        );

        $other = $this->makeRegistration(
            $period,
            $path,
            $major,
            'SITI WHATSAPP OTHER'
        );

        $this->makeWhatsappLog(
            $target,
            'SUCCESS',
            'registration_success',
            '081244440001',
            'wamid.budi'
        );

        $this->makeWhatsappLog(
            $other,
            'SUCCESS',
            'registration_success',
            '081244440002',
            'wamid.siti'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('admin.whatsapp-logs.index', [
                    'period_id' => $period->id,
                    'q' => 'BUDI WHATSAPP TARGET',
                ])
            );

        $response
            ->assertOk()
            ->assertSee('BUDI WHATSAPP TARGET')
            ->assertDontSee('SITI WHATSAPP OTHER');
    }

    public function test_whatsapp_log_can_search_registration_number(): void
    {
        [
            $user,
            $period,
            $path,
            $major,
        ] = $this->makeFixture();

        $registration = $this->makeRegistration(
            $period,
            $path,
            $major,
            'SEARCH NUMBER TEST'
        );

        $this->makeWhatsappLog(
            $registration,
            'SUCCESS',
            'registration_success',
            '081255550001',
            'wamid.number'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('admin.whatsapp-logs.index', [
                    'period_id' => $period->id,
                    'q' => $registration->registration_number,
                ])
            );

        $response
            ->assertOk()
            ->assertSee('SEARCH NUMBER TEST');
    }

    public function test_whatsapp_log_can_search_phone_number(): void
    {
        [
            $user,
            $period,
            $path,
            $major,
        ] = $this->makeFixture();

        $registration = $this->makeRegistration(
            $period,
            $path,
            $major,
            'SEARCH PHONE TEST'
        );

        $this->makeWhatsappLog(
            $registration,
            'SUCCESS',
            'registration_success',
            '081266661234',
            'wamid.phone'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('admin.whatsapp-logs.index', [
                    'period_id' => $period->id,
                    'q' => '081266661234',
                ])
            );

        $response
            ->assertOk()
            ->assertSee('SEARCH PHONE TEST')
            ->assertSee('081266661234');
    }

    public function test_whatsapp_log_can_search_provider_message_id(): void
    {
        [
            $user,
            $period,
            $path,
            $major,
        ] = $this->makeFixture();

        $registration = $this->makeRegistration(
            $period,
            $path,
            $major,
            'MESSAGE ID TARGET'
        );

        $this->makeWhatsappLog(
            $registration,
            'SUCCESS',
            'registration_success',
            '081277770001',
            'wamid.unique-search-test'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('admin.whatsapp-logs.index', [
                    'period_id' => $period->id,
                    'q' => 'unique-search-test',
                ])
            );

        $response
            ->assertOk()
            ->assertSee('MESSAGE ID TARGET');
    }

    public function test_whatsapp_log_summary_counts_selected_period(): void
    {
        [
            $user,
            $period,
            $path,
            $major,
        ] = $this->makeFixture();

        $pending = $this->makeRegistration(
            $period,
            $path,
            $major,
            'SUMMARY PENDING'
        );

        $success = $this->makeRegistration(
            $period,
            $path,
            $major,
            'SUMMARY SUCCESS'
        );

        $failed = $this->makeRegistration(
            $period,
            $path,
            $major,
            'SUMMARY FAILED'
        );

        $this->makeWhatsappLog(
            $pending,
            'PENDING',
            'registration_success',
            '081288880001'
        );

        $this->makeWhatsappLog(
            $success,
            'SUCCESS',
            'registration_accepted',
            '081288880002',
            'wamid.summary-success'
        );

        $this->makeWhatsappLog(
            $failed,
            'FAILED',
            'registration_rejected',
            '081288880003'
        );

        $response = $this
            ->actingAs($user)
            ->get(
                route('admin.whatsapp-logs.index', [
                    'period_id' => $period->id,
                ])
            );

        $response->assertOk();

        /*
         * Lebih kuat daripada hanya mencari angka pada HTML.
         * Kita periksa data summary yang benar-benar diberikan
         * controller kepada view.
         */
        $response->assertViewHas(
            'summary',
            function (array $summary): bool {
                return $summary['total'] === 3
                    && $summary['pending'] === 1
                    && $summary['success'] === 1
                    && $summary['failed'] === 1;
            }
        );
    }

    private function makeFixture(): array
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $school = School::create([
            'name' => 'SMK WHATSAPP LOG TEST',
        ]);

        $period = PpdbPeriod::create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'status' => 'OPEN',
            'is_active' => true,
            'number_year' => 2027,
        ]);

        $path = AdmissionPath::create([
            'period_id' => $period->id,
            'name' => 'UMUM',
            'code' => 'UMUM',
            'is_active' => true,
        ]);

        $major = Major::create([
            'school_id' => $school->id,
            'code' => 'TST',
            'name' => 'JURUSAN WHATSAPP TEST',
            'is_active' => true,
        ]);

        return [
            $user,
            $period,
            $path,
            $major,
        ];
    }

    private function makeRegistration(
        PpdbPeriod $period,
        AdmissionPath $path,
        Major $major,
        string $name
    ): Registration {
        static $sequence = 0;

        $sequence++;

        return Registration::create([
            'period_id' => $period->id,
            'wave_id' => null,
            'admission_path_id' => $path->id,
            'major_id' => $major->id,

            'registration_number' =>
                'WA-TEST-'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'nik' =>
                '3377777777'
                .str_pad(
                    (string) $sequence,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),

            'full_name' => $name,
            'origin_school' => 'SMP WHATSAPP TEST',

            'whatsapp' =>
                '08127777'
                .str_pad(
                    (string) $sequence,
                    4,
                    '0',
                    STR_PAD_LEFT
                ),

            'data_source' => 'ADMIN',
            'status' => 'REGISTERED',
            'registered_at' => now(),
        ]);
    }

    private function makeWhatsappLog(
        Registration $registration,
        string $status,
        string $messageType,
        string $phone,
        ?string $providerMessageId = null
    ): WhatsappLog {
        return WhatsappLog::create([
            'registration_id' => $registration->id,
            'provider' => 'meta',
            'phone' => $phone,
            'message_type' => $messageType,
            'message' => 'Pesan WhatsApp feature test.',
            'status' => $status,
            'provider_message_id' => $providerMessageId,
            'retry_count' => 0,
        ]);
    }
}
