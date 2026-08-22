<?php

namespace Tests\Feature;

use App\Models\OriginSchool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicRegistrationOriginSchoolTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_public_registration_with_active_origin_school_master_succeeds(): void
    {
        Carbon::setTestNow(
            Carbon::parse(
                '2027-02-15 10:00:00',
                config('app.timezone')
            )
        );

        $fixture = $this->makeBaseFixture();

        $originSchool = OriginSchool::create([
            'name' => 'SMP TEST MASTER',
            'type' => 'SMP',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response = $this->post(
            route('registration.store'),
            $this->validPayload(
                $fixture['period_id'],
                $fixture['major_id'],
                '3305011502071001',
                [
                    'origin_school_id' => (string) $originSchool->id,
                    'origin_school_other' => null,
                ]
            )
        );

        $registration = DB::table('registrations')
            ->where('nik', '3305011502071001')
            ->first();

        $this->assertNotNull($registration);

        $this->assertSame(
            'SMP TEST MASTER',
            $registration->origin_school
        );

        $response->assertRedirect(
            route(
                'registration.success',
                [
                    'publicToken' => $registration->public_token,
                ]
            )
        );
    }

    public function test_public_registration_with_other_origin_school_succeeds(): void
    {
        Carbon::setTestNow(
            Carbon::parse(
                '2027-02-15 10:00:00',
                config('app.timezone')
            )
        );

        $fixture = $this->makeBaseFixture();

        $response = $this->post(
            route('registration.store'),
            $this->validPayload(
                $fixture['period_id'],
                $fixture['major_id'],
                '3305011502071002',
                [
                    'origin_school_id' => 'OTHER',
                    'origin_school_other' => 'SMP Swasta Contoh',
                ]
            )
        );

        $registration = DB::table('registrations')
            ->where('nik', '3305011502071002')
            ->first();

        $this->assertNotNull($registration);

        $this->assertSame(
            'SMP SWASTA CONTOH',
            $registration->origin_school
        );

        $response->assertRedirect(
            route(
                'registration.success',
                [
                    'publicToken' => $registration->public_token,
                ]
            )
        );
    }

    public function test_inactive_origin_school_master_is_rejected(): void
    {
        Carbon::setTestNow(
            Carbon::parse(
                '2027-02-15 10:00:00',
                config('app.timezone')
            )
        );

        $fixture = $this->makeBaseFixture();

        $originSchool = OriginSchool::create([
            'name' => 'SMP NONAKTIF',
            'type' => 'SMP',
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $response = $this
            ->from(route('registration.create'))
            ->post(
                route('registration.store'),
                $this->validPayload(
                    $fixture['period_id'],
                    $fixture['major_id'],
                    '3305011502071003',
                    [
                        'origin_school_id' => (string) $originSchool->id,
                        'origin_school_other' => null,
                    ]
                )
            );

        $response->assertRedirect(
            route('registration.create')
        );

        $response->assertSessionHasErrors(
            'origin_school_id'
        );

        $this->assertDatabaseMissing(
            'registrations',
            [
                'nik' => '3305011502071003',
            ]
        );
    }

    public function test_other_origin_school_requires_manual_name(): void
    {
        Carbon::setTestNow(
            Carbon::parse(
                '2027-02-15 10:00:00',
                config('app.timezone')
            )
        );

        $fixture = $this->makeBaseFixture();

        $response = $this
            ->from(route('registration.create'))
            ->post(
                route('registration.store'),
                $this->validPayload(
                    $fixture['period_id'],
                    $fixture['major_id'],
                    '3305011502071004',
                    [
                        'origin_school_id' => 'OTHER',
                        'origin_school_other' => '',
                    ]
                )
            );

        $response->assertRedirect(
            route('registration.create')
        );

        $response->assertSessionHasErrors(
            'origin_school_other'
        );

        $this->assertDatabaseMissing(
            'registrations',
            [
                'nik' => '3305011502071004',
            ]
        );
    }

    private function makeBaseFixture(): array
    {
        $now = now();

        $schoolId = DB::table('schools')
            ->insertGetId([
                'name' => 'SMK TEST ORIGIN SCHOOL',
                'npsn' => '55555555',
                'address' => null,
                'village' => null,
                'district' => null,
                'city' => 'Kebumen',
                'province' => 'Jawa Tengah',
                'postal_code' => '54311',
                'phone' => null,
                'whatsapp' => null,
                'email' => null,
                'website' => null,
                'logo_path' => null,
                'favicon_path' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        $periodId = DB::table('ppdb_periods')
            ->insertGetId([
                'school_id' => $schoolId,
                'name' => '2027/2028',
                'year_start' => 2027,
                'year_end' => 2028,
                'registration_open' => '2027-01-01',
                'registration_close' => '2027-06-30',
                'status' => 'OPEN',
                'is_active' => true,
                'principal_name' => null,
                'principal_nip' => null,
                'number_prefix' => 'MARSA',
                'number_year' => 2027,
                'number_digits' => 4,
                'include_major_code' => true,
                'default_reenroll_fee' => 250000,
                'notes' => null,
                'archived_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        DB::table('admission_paths')->insert([
            'period_id' => $periodId,
            'name' => 'Khusus',
            'code' => 'KHUSUS',
            'start_date' => '2027-01-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
            'sort_order' => 1,
            'description' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $majorId = DB::table('majors')
            ->insertGetId([
                'school_id' => $schoolId,
                'code' => 'TKRO',
                'name' => 'Teknik Kendaraan Ringan Otomotif',
                'short_name' => 'TKRO',
                'description' => null,
                'icon_path' => null,
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

        DB::table('period_majors')->insert([
            'period_id' => $periodId,
            'major_id' => $majorId,
            'quota' => null,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [
            'period_id' => $periodId,
            'major_id' => $majorId,
        ];
    }

    private function validPayload(
        int $periodId,
        int $majorId,
        string $nik,
        array $originSchoolData
    ): array {
        return array_merge(
            [
                'period_id' => $periodId,
                'major_id' => $majorId,

                'nik' => $nik,
                'nisn' => '1234567890',
                'full_name' => 'TEST ASAL SEKOLAH',

                'birth_place' => 'Kebumen',
                'birth_date' => '2010-01-15',
                'gender' => 'L',
                'religion' => 'Islam',

                'hamlet' => 'Karanganyar',
                'rt' => '001',
                'rw' => '002',
                'village' => 'Test Village',
                'district' => 'Kebumen',
                'city' => 'Kebumen',
                'province' => 'Jawa Tengah',
                'postal_code' => '54311',

                'father_name' => 'AYAH TEST',
                'mother_name' => 'IBU TEST',
                'father_job' => 'Wiraswasta',
                'mother_job' => 'Ibu Rumah Tangga',

                'whatsapp' => '081234567890',

                'graduation_score' => 88.50,

                'relief_options' => [],
                'special_programs' => [],

                'referrer_name' => null,
                'referrer_source' => null,

                'notes' => 'Test origin school.',
            ],
            $originSchoolData
        );
    }
}