<?php

namespace Tests\Feature;

use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\School;
use App\Services\RegistrationNumberService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegistrationNumberServiceTest extends TestCase
{
    use DatabaseTransactions;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::query()->create([
            'name' => 'SMK TEST REGISTRATION NUMBER',
            'npsn' => '99887766',
            'city' => 'Kebumen',
            'province' => 'Jawa Tengah',
            'postal_code' => '54311',
        ]);
    }

    public function test_sequential_generation_increments_same_major_sequence(): void
    {
        $period = $this->makePeriod(true);
        $major = $this->makeMajor('RPL');

        $service = app(RegistrationNumberService::class);

        $first = $service->generate($period, $major);
        $second = $service->generate($period, $major);
        $third = $service->generate($period, $major);

        $this->assertSame(
            'MARSA-2027-RPL-0001',
            $first
        );

        $this->assertSame(
            'MARSA-2027-RPL-0002',
            $second
        );

        $this->assertSame(
            'MARSA-2027-RPL-0003',
            $third
        );

        $this->assertDatabaseHas(
            'registration_sequences',
            [
                'period_id' => $period->id,
                'major_id' => $major->id,
                'sequence_key' => 'MAJOR:' . $major->id,
                'current_number' => 3,
            ]
        );

        $this->assertSame(
            1,
            DB::table('registration_sequences')
                ->where('period_id', $period->id)
                ->where(
                    'sequence_key',
                    'MAJOR:' . $major->id
                )
                ->count()
        );
    }

    public function test_each_major_has_independent_sequence(): void
    {
        $period = $this->makePeriod(true);

        $rpl = $this->makeMajor('RPL');
        $tkro = $this->makeMajor('TKRO');

        $service = app(RegistrationNumberService::class);

        $rplFirst = $service->generate(
            $period,
            $rpl
        );

        $rplSecond = $service->generate(
            $period,
            $rpl
        );

        $tkroFirst = $service->generate(
            $period,
            $tkro
        );

        $this->assertSame(
            'MARSA-2027-RPL-0001',
            $rplFirst
        );

        $this->assertSame(
            'MARSA-2027-RPL-0002',
            $rplSecond
        );

        $this->assertSame(
            'MARSA-2027-TKRO-0001',
            $tkroFirst
        );

        $this->assertDatabaseHas(
            'registration_sequences',
            [
                'period_id' => $period->id,
                'major_id' => $rpl->id,
                'sequence_key' =>
                    'MAJOR:' . $rpl->id,
                'current_number' => 2,
            ]
        );

        $this->assertDatabaseHas(
            'registration_sequences',
            [
                'period_id' => $period->id,
                'major_id' => $tkro->id,
                'sequence_key' =>
                    'MAJOR:' . $tkro->id,
                'current_number' => 1,
            ]
        );
    }

    public function test_global_sequence_is_used_when_major_code_is_disabled(): void
    {
        $period = $this->makePeriod(false);

        $rpl = $this->makeMajor('RPL');
        $tkro = $this->makeMajor('TKRO');

        $service = app(RegistrationNumberService::class);

        $first = $service->generate(
            $period,
            $rpl
        );

        $second = $service->generate(
            $period,
            $tkro
        );

        $third = $service->generate(
            $period
        );

        $this->assertSame(
            'MARSA-2027-0001',
            $first
        );

        $this->assertSame(
            'MARSA-2027-0002',
            $second
        );

        $this->assertSame(
            'MARSA-2027-0003',
            $third
        );

        $this->assertDatabaseHas(
            'registration_sequences',
            [
                'period_id' => $period->id,
                'major_id' => null,
                'sequence_key' => 'GLOBAL',
                'current_number' => 3,
            ]
        );

        $this->assertSame(
            1,
            DB::table('registration_sequences')
                ->where('period_id', $period->id)
                ->where('sequence_key', 'GLOBAL')
                ->count()
        );
    }

    private function makePeriod(
        bool $includeMajorCode
    ): PpdbPeriod {
        return PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => $includeMajorCode
                ? '2027/2028'
                : '2028/2029',
            'year_start' => $includeMajorCode
                ? 2027
                : 2028,
            'year_end' => $includeMajorCode
                ? 2028
                : 2029,
            'registration_open' => '2027-01-01',
            'registration_close' => '2027-06-30',
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'MARSA',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' =>
                $includeMajorCode,
            'default_reenroll_fee' => 250000,
        ]);
    }

    private function makeMajor(
        string $code
    ): Major {
        return Major::query()->create([
            'school_id' => $this->school->id,
            'code' => $code,
            'name' => 'Jurusan ' . $code,
            'short_name' => $code,
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }
}