<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRegistrationStatusTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private PpdbPeriod $period;
    private AdmissionPath $admissionPath;
    private Major $major;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::query()->create([
            'name' => 'SMK PUBLIC STATUS TEST',
            'npsn' => '12345672',
        ]);

        $this->period = PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'MARSA',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 0,
        ]);

        $this->admissionPath = AdmissionPath::query()->create([
            'period_id' => $this->period->id,
            'name' => 'Umum',
            'code' => 'UMUM',
            'start_date' => '2027-01-01',
            'end_date' => '2027-06-30',
            'is_active' => true,
            'sort_order' => 10,
        ]);

        $this->major = Major::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
            'is_active' => true,
        ]);
    }

    public function test_public_status_can_be_opened_without_login(): void
    {
        $registration = $this->makeRegistration();

        $this->get(route(
            'registration.status',
            $registration->public_token
        ))
            ->assertOk()
            ->assertSee($registration->registration_number)
            ->assertSee($registration->full_name)
            ->assertSee('Terdaftar');
    }

    public function test_invalid_public_token_returns_404(): void
    {
        $this->get(route(
            'registration.status',
            '01AAAAAAAAAAAAAAAAAAAAAAAA'
        ))->assertNotFound();
    }

    public function test_public_status_remains_available_for_closed_period(): void
    {
        $registration = $this->makeRegistration();

        $this->period->update([
            'status' => 'CLOSED',
            'is_active' => false,
        ]);

        $this->get(route(
            'registration.status',
            $registration->public_token
        ))
            ->assertOk()
            ->assertSee($registration->registration_number);
    }

    public function test_public_status_displays_current_registration_status(): void
    {
        $registration = $this->makeRegistration();

        $registration->update([
            'status' => 'ACCEPTED',
            'accepted_at' => now(),
        ]);

        $this->get(route(
            'registration.status',
            $registration->public_token
        ))
            ->assertOk()
            ->assertSee('Diterima')
            ->assertDontSee('Status: Terdaftar');
    }

    public function test_public_status_does_not_expose_sensitive_fields(): void
    {
        $registration = $this->makeRegistration([
            'nik' => '3377777777000099',
            'nisn' => '9988776655',
            'whatsapp' => '081299999999',
            'father_name' => 'AYAH RAHASIA',
            'mother_name' => 'IBU RAHASIA',
            'referrer_name' => 'REFERRER INTERNAL',
            'referrer_source' => 'SUMBER INTERNAL',
            'notes' => 'CATATAN INTERNAL RAHASIA',
        ]);

        $this->get(route(
            'registration.status',
            $registration->public_token
        ))
            ->assertOk()
            ->assertDontSee('3377777777000099')
            ->assertDontSee('9988776655')
            ->assertDontSee('081299999999')
            ->assertDontSee('AYAH RAHASIA')
            ->assertDontSee('IBU RAHASIA')
            ->assertDontSee('REFERRER INTERNAL')
            ->assertDontSee('SUMBER INTERNAL')
            ->assertDontSee('CATATAN INTERNAL RAHASIA');
    }

    public function test_public_status_displays_all_supported_status_labels(): void
    {
        $statuses = [
            'REGISTERED' => 'Terdaftar',
            'ACCEPTED' => 'Diterima',
            'REJECTED' => 'Ditolak',
            'REENROLLED' => 'Daftar Ulang',
            'WITHDRAWN' => 'Mengundurkan Diri',
        ];

        foreach ($statuses as $status => $label) {
            $registration = $this->makeRegistration([
                'registration_number' => 'MARSA-2027-RPL-'.$status,
                'nik' => match ($status) {
                    'REGISTERED' => '3377777777000011',
                    'ACCEPTED' => '3377777777000012',
                    'REJECTED' => '3377777777000013',
                    'REENROLLED' => '3377777777000014',
                    'WITHDRAWN' => '3377777777000015',
                },
                'status' => $status,
                'public_token' => match ($status) {
                    'REGISTERED' => '01HAAAAAAAAAAAAAAAAAAAAAAA',
                    'ACCEPTED' => '01HBBBBBBBBBBBBBBBBBBBBBBB',
                    'REJECTED' => '01HCCCCCCCCCCCCCCCCCCCCCCC',
                    'REENROLLED' => '01HDDDDDDDDDDDDDDDDDDDDDDD',
                    'WITHDRAWN' => '01HEEEEEEEEEEEEEEEEEEEEEEE',
                },
            ]);

            $this->get(route(
                'registration.status',
                $registration->public_token
            ))
                ->assertOk()
                ->assertSee($label);
        }
    }

    public function test_success_page_contains_public_status_link(): void
    {
        $registration = $this->makeRegistration();

        $response = $this->get(route(
            'registration.success',
            $registration->public_token
        ));

        $response
            ->assertOk()
            ->assertSee(
                route(
                    'registration.status',
                    $registration->public_token
                ),
                false
            )
            ->assertSee('Cek Status Pendaftaran');
    }

    public function test_success_page_displays_current_registration_status(): void
    {
        $registration = $this->makeRegistration([
            'status' => 'ACCEPTED',
        ]);

        $this->get(route(
            'registration.success',
            $registration->public_token
        ))
            ->assertOk()
            ->assertSee('Diterima');
    }

    private function makeRegistration(array $overrides = []): Registration
    {
        $publicToken = $overrides['public_token']
            ?? '01HZZZZZZZZZZZZZZZZZZZZZZZ';

        unset($overrides['public_token']);

        $registration = Registration::query()->create(array_merge([
            'period_id' => $this->period->id,
            'admission_path_id' => $this->admissionPath->id,
            'major_id' => $this->major->id,
            'registration_number' => 'MARSA-2027-RPL-0001',
            'nik' => '3377777777000001',
            'full_name' => 'SISWA STATUS PUBLIK',
            'whatsapp' => '081277770001',
            'data_source' => 'PUBLIC',
            'status' => 'REGISTERED',
            'registered_at' => now(),
        ], $overrides));

        $registration
            ->forceFill([
                'public_token' => $publicToken,
            ])
            ->save();

        return $registration->fresh();
    }
}