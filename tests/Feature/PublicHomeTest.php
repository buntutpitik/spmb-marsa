<?php

namespace Tests\Feature;

use App\Models\PpdbPeriod;
use App\Models\PublicPageSetting;
use App\Models\School;
use App\Models\User;
use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\SpecialProgram;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicHomeTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    private PpdbPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::query()->create([
            'name' => 'SMK PUBLIC HOME TEST',
            'npsn' => '12345678',
            'address' => 'Jl. Test No. 1',
            'phone' => '0287123456',
            'whatsapp' => '081234567890',
            'email' => 'info@example.test',
            'website' => 'https://example.test',
        ]);

        $this->period = PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'registration_open' => '2027-01-01',
            'registration_close' => '2027-06-30',
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'MARSA',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ]);
    }

    public function test_public_home_is_accessible_without_login(): void
    {
        $this->get(
            route('home')
        )
            ->assertOk()
            ->assertSee($this->school->name);
    }

    public function test_internal_dashboard_still_requires_login(): void
    {
        $this->get(
            route('dashboard')
        )
            ->assertRedirect(
                route('login')
            );
    }

    public function test_authenticated_internal_user_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(
                route('dashboard')
            )
            ->assertOk();
    }

    public function test_public_home_uses_saved_public_page_content(): void
    {
        PublicPageSetting::query()->create([
            'school_id' => $this->school->id,
            'hero_title' => 'Ayo Bergabung Bersama Kami',
            'hero_subtitle' => 'SPMB Tahun Ajaran 2027/2028',
            'hero_description' => 'Pendaftaran siswa baru telah tersedia.',
            'announcement_title' => 'Pengumuman Pendaftaran',
            'announcement_body' => 'Silakan melakukan pendaftaran secara online.',
            'show_announcement' => true,
            'requirements' => "NIK\nNISN\nNomor WhatsApp",
            'show_requirements' => true,
            'registration_steps' => "Isi formulir\nPeriksa data\nKirim pendaftaran\nCetak kartu",
            'show_registration_steps' => true,
            'reenrollment_information' => 'Daftar ulang dilakukan setelah dinyatakan diterima.',
            'show_reenrollment_information' => true,
            'show_contact' => true,
        ]);

        $this->get(
            route('home')
        )
            ->assertOk()
            ->assertSee('Ayo Bergabung Bersama Kami')
            ->assertSee('SPMB Tahun Ajaran 2027/2028')
            ->assertSee('Pengumuman Pendaftaran')
            ->assertSee('NIK')
            ->assertSee('Isi formulir')
            ->assertSee('Daftar ulang dilakukan setelah dinyatakan diterima.')
            ->assertSee('href="#cara-mendaftar"', false)
            ->assertSee('id="cara-mendaftar"', false);
    }

    public function test_hidden_public_sections_are_not_rendered(): void
    {
        PublicPageSetting::query()->create([
            'school_id' => $this->school->id,
            'announcement_title' => 'RAHASIA PENGUMUMAN',
            'announcement_body' => 'RAHASIA ISI',
            'show_announcement' => false,
            'requirements' => 'RAHASIA PERSYARATAN',
            'show_requirements' => false,
            'registration_steps' => 'RAHASIA LANGKAH',
            'show_registration_steps' => false,
            'reenrollment_information' => 'RAHASIA DAFTAR ULANG',
            'show_reenrollment_information' => false,
            'show_contact' => false,
        ]);

        $this->get(
            route('home')
        )
            ->assertOk()
            ->assertDontSee('RAHASIA PENGUMUMAN')
            ->assertDontSee('RAHASIA ISI')
            ->assertDontSee('RAHASIA PERSYARATAN')
            ->assertDontSee('RAHASIA LANGKAH')
            ->assertDontSee('RAHASIA DAFTAR ULANG')
            ->assertDontSee('href="#cara-mendaftar"', false);
    }

    public function test_registration_steps_navigation_is_hidden_when_steps_are_empty(): void
    {
        PublicPageSetting::query()->create([
            'school_id' => $this->school->id,
            'registration_steps' => null,
            'show_registration_steps' => true,
        ]);

        $this->get(
            route('home')
        )
            ->assertOk()
            ->assertDontSee('href="#cara-mendaftar"', false)
            ->assertDontSee('id="cara-mendaftar"', false);
    }

    public function test_registration_is_available_when_an_admission_path_is_active_today(): void
    {
        Carbon::setTestNow(
            '2027-03-01 08:00:00'
        );

        AdmissionPath::query()->create([
            'period_id' => $this->period->id,
            'name' => 'UMUM',
            'code' => 'UMUM',
            'start_date' => '2027-02-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get(
            route('home')
        )
            ->assertOk()
            ->assertViewHas(
                'registrationAvailable',
                true
            )
            ->assertViewHas(
                'activePath',
                fn ($path) => $path?->code === 'UMUM'
            );

        Carbon::setTestNow();
    }

    public function test_registration_is_not_available_when_no_admission_path_is_active_today(): void
    {
        Carbon::setTestNow(
            '2027-03-01 08:00:00'
        );

        AdmissionPath::query()->create([
            'period_id' => $this->period->id,
            'name' => 'KHUSUS',
            'code' => 'KHUSUS',
            'start_date' => '2027-01-01',
            'end_date' => '2027-01-31',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get(
            route('home')
        )
            ->assertOk()
            ->assertViewHas(
                'registrationAvailable',
                false
            )
            ->assertViewHas(
                'activePath',
                null
            );

        Carbon::setTestNow();
    }

    public function test_public_home_only_loads_active_period_options(): void
    {
        Carbon::setTestNow(
            '2027-03-01 08:00:00'
        );

        $activeMajor = Major::query()->create([
            'school_id' => $this->school->id,
            'code' => 'RPL',
            'name' => 'Rekayasa Perangkat Lunak',
            'short_name' => 'RPL',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $inactiveMajor = Major::query()->create([
            'school_id' => $this->school->id,
            'code' => 'TKRO',
            'name' => 'Teknik Kendaraan Ringan',
            'short_name' => 'TKRO',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $inactivePivotMajor = Major::query()->create([
            'school_id' => $this->school->id,
            'code' => 'TSM',
            'name' => 'Teknik Sepeda Motor',
            'short_name' => 'TSM',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $this->period->majors()->attach(
            $activeMajor->id,
            [
                'quota' => 100,
                'is_active' => true,
            ]
        );

        $this->period->majors()->attach(
            $inactiveMajor->id,
            [
                'quota' => 100,
                'is_active' => true,
            ]
        );

        $this->period->majors()->attach(
            $inactivePivotMajor->id,
            [
                'quota' => 100,
                'is_active' => false,
            ]
        );

        $activePath = AdmissionPath::query()->create([
            'period_id' => $this->period->id,
            'name' => 'Jalur Umum',
            'code' => 'UMUM',
            'start_date' => '2027-02-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        AdmissionPath::query()->create([
            'period_id' => $this->period->id,
            'name' => 'Jalur Tidak Aktif',
            'code' => 'NONAKTIF',
            'start_date' => '2027-02-01',
            'end_date' => '2027-06-30',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $activeProgram = SpecialProgram::query()->create([
            'name' => 'Program Tahfidz',
            'slug' => 'program-tahfidz',
            'description' => 'Program khusus aktif.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $inactiveProgram = SpecialProgram::query()->create([
            'name' => 'Program Tidak Aktif',
            'slug' => 'program-tidak-aktif',
            'description' => 'Tidak boleh tampil.',
            'is_active' => false,
            'sort_order' => 2,
        ]);

        $inactivePivotProgram = SpecialProgram::query()->create([
            'name' => 'Program Pivot Nonaktif',
            'slug' => 'program-pivot-nonaktif',
            'description' => 'Tidak boleh tampil.',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $this->period->specialPrograms()->attach(
            $activeProgram->id,
            [
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $this->period->specialPrograms()->attach(
            $inactiveProgram->id,
            [
                'is_active' => true,
                'sort_order' => 2,
            ]
        );

        $this->period->specialPrograms()->attach(
            $inactivePivotProgram->id,
            [
                'is_active' => false,
                'sort_order' => 3,
            ]
        );

        $response = $this->get(
            route('home')
        );

        $response
            ->assertOk()
            ->assertViewHas(
                'period',
                function ($period) use (
                    $activeMajor,
                    $activePath,
                    $activeProgram
                ) {
                    return $period->majors
                        ->pluck('id')
                        ->all()
                        === [$activeMajor->id]
                        && $period->admissionPaths
                            ->pluck('id')
                            ->all()
                            === [$activePath->id]
                        && $period->specialPrograms
                            ->pluck('id')
                            ->all()
                            === [$activeProgram->id];
                }
            )
            ->assertViewHas(
                'activePath',
                fn ($path) => $path?->is($activePath)
            )
            ->assertViewHas(
                'registrationAvailable',
                true
            );

        Carbon::setTestNow();
    }

    public function test_public_home_still_works_when_there_is_no_open_period(): void
    {
        $this->period->update([
            'status' => 'CLOSED',
            'is_active' => false,
        ]);

        $this->get(
            route('home')
        )
            ->assertOk()
            ->assertSee($this->school->name)
            ->assertViewHas(
                'period',
                null
            )
            ->assertViewHas(
                'activePath',
                null
            )
            ->assertViewHas(
                'registrationAvailable',
                false
            );
    }

    public function test_public_home_reports_upcoming_registration_state(): void
    {
        Carbon::setTestNow(
            '2026-08-29 08:00:00'
        );

        $this->get(
            route('home')
        )
            ->assertOk()
            ->assertViewHas(
                'registrationState',
                'UPCOMING'
            );

        Carbon::setTestNow();
    }

    public function test_public_home_reports_open_registration_state(): void
    {
        Carbon::setTestNow(
            '2027-03-01 08:00:00'
        );

        AdmissionPath::query()->create([
            'period_id' => $this->period->id,
            'name' => 'UMUM',
            'code' => 'UMUM',
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get(
            route('home')
        )
            ->assertOk()
            ->assertViewHas(
                'registrationState',
                'OPEN'
            );

        Carbon::setTestNow();
    }

    public function test_public_home_reports_closed_registration_state(): void
    {
        Carbon::setTestNow(
            '2027-07-01 08:00:00'
        );

        $this->get(
            route('home')
        )
            ->assertOk()
            ->assertViewHas(
                'registrationState',
                'CLOSED'
            );

        Carbon::setTestNow();
    }

    public function test_public_home_has_admin_login_link(): void
    {
        $this->get(
            route('home')
        )
            ->assertOk()
            ->assertSee('Login Admin')
            ->assertSee(
                'href="'.route('login').'"',
                false
            );
    }
}