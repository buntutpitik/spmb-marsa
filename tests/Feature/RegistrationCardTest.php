<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationCardTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private PpdbPeriod $period;
    private Major $major;
    private AdmissionPath $admissionPath;
    private Registration $registration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::query()->create([
            'name' => 'SMK MAARIF 9 KEBUMEN',
            'npsn' => '20305070',
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

        $this->major = Major::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
            'capacity' => 100,
            'is_active' => true,
        ]);

        $this->period->majors()->attach(
            $this->major->id,
            ['is_active' => true]
        );

       $this->admissionPath = AdmissionPath::query()->create([
            'period_id' => $this->period->id,
            'name' => 'UMUM',
            'code' => 'UMUM',
            'is_active' => true,
        ]);

        $this->registration = Registration::query()->create([
            'period_id' => $this->period->id,
            'admission_path_id' => $this->admissionPath->id,
            'major_id' => $this->major->id,
            'registration_number' => 'MARSA-2027-RPL-0001',
            'nik' => '3305010101010001',
            'nisn' => '1234567890',
            'full_name' => 'BUDI SANTOSO',
            'gender' => 'L',
            'origin_school' => 'SMP NEGERI 1 KEBUMEN',
            'whatsapp' => '081234567890',
            'data_source' => 'PUBLIC',
            'status' => 'REGISTERED',
            'registered_at' => now(),
        ]);

        $this->registration->forceFill([
            'public_token' => '01KTESTCARDPUBLICTOKEN001',
        ])->save();

        $this->registration->refresh();
    }

    public function test_public_card_can_be_downloaded_without_login(): void
    {
        $response = $this->get(
            route(
                'registration.card',
                $this->registration->public_token
            )
        );

        $response
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/pdf'
            );
    }

    public function test_invalid_public_token_returns_404(): void
    {
        $this->get(
            route(
                'registration.card',
                '01KINVALIDCARDTOKEN0000000'
            )
        )->assertNotFound();
    }

    public function test_public_card_remains_available_for_closed_period(): void
    {
        $this->period->update([
            'status' => 'CLOSED',
            'is_active' => false,
        ]);

        $this->get(
            route(
                'registration.card',
                $this->registration->public_token
            )
        )
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/pdf'
            );
    }

    public function test_guest_cannot_use_admin_card_route(): void
    {
        $this->get(
            route(
                'admin.registrations.card',
                $this->registration
            )
        )->assertRedirect(route('login'));
    }

    public function test_all_operational_roles_can_download_admin_card(): void
    {
        foreach ([
            'SUPERADMIN',
            'ADMIN',
            'PANITIA',
            'BENDAHARA',
        ] as $role) {
            $user = User::factory()->create([
                'role' => $role,
                'is_active' => true,
            ]);

            $this->actingAs($user)
                ->get(
                    route(
                        'admin.registrations.card',
                        $this->registration
                    )
                )
                ->assertOk()
                ->assertHeader(
                    'content-type',
                    'application/pdf'
                );

            auth()->logout();
        }
    }

    public function test_admin_card_remains_available_for_closed_period(): void
    {
        $this->period->update([
            'status' => 'CLOSED',
            'is_active' => false,
        ]);

        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(
                route(
                    'admin.registrations.card',
                    $this->registration
                )
            )
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/pdf'
            );
    }

    public function test_admin_card_without_public_token_can_still_be_downloaded(): void
    {
        $this->registration->forceFill([
            'public_token' => null,
        ])->save();

        $this->period->update([
            'status' => 'CLOSED',
            'is_active' => false,
        ]);

        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(
                route(
                    'admin.registrations.card',
                    $this->registration
                )
            )
            ->assertOk()
            ->assertHeader(
                'content-type',
                'application/pdf'
            );
    }

    public function test_success_page_contains_registration_card_link(): void
    {
        $this->get(
            route(
                'registration.success',
                $this->registration->public_token
            )
        )
            ->assertOk()
            ->assertSee('Cetak Kartu Pendaftaran')
            ->assertSee(
                route(
                    'registration.card',
                    $this->registration->public_token
                ),
                false
            );
    }

    public function test_admin_detail_contains_registration_card_link(): void
    {
        $user = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get(
                route(
                    'admin.registrations.show',
                    [
                        'registration' => $this->registration,
                        'period_id' => $this->period->id,
                    ]
                )
            )
            ->assertOk()
            ->assertSee('Print Kartu')
            ->assertSee(
                route(
                    'admin.registrations.card',
                    $this->registration
                ),
                false
            );
    }
}