<?php

namespace Tests\Feature;

use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use App\Models\User;
use App\Models\AdmissionPath;
use App\Models\Major;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHistoricalDataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private PpdbPeriod $historicalPeriod;

    private PpdbPeriod $activePeriod;

    private Major $major;

    private AdmissionPath $historicalPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $this->school = School::query()->create([
            'name' => 'SMK HISTORICAL DATA TEST',
        ]);

        $this->historicalPeriod = $this->makePeriod(
            '2026/2027',
            2026,
            false,
            'CLOSED'
        );

        $this->activePeriod = $this->makePeriod(
            '2027/2028',
            2027,
            true,
            'OPEN'
        );

        $this->major = Major::query()->create([
            'school_id' => $this->school->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
            'is_active' => true,
        ]);

        $this->historicalPath = AdmissionPath::query()->create([
            'period_id' => $this->historicalPeriod->id,
            'name' => 'KHUSUS',
            'code' => 'KHUSUS',
            'start_date' => '2026-01-01',
            'end_date' => '2026-06-30',
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_admin_can_open_historical_data_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.historical.index'))
            ->assertOk()
            ->assertSee('Data Historis');
    }

    public function test_guest_cannot_open_historical_data_page(): void
    {
        $this->get(route('admin.historical.index'))
            ->assertRedirect(route('login'));
    }

    public function test_historical_page_lists_closed_periods(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.historical.index'))
            ->assertOk()
            ->assertSee('2026/2027');
    }

    public function test_historical_page_does_not_list_active_period_as_archive(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.historical.index'));

        $response
            ->assertOk()
            ->assertSee('2026/2027')
            ->assertSee('CLOSED')
            ->assertSee('HISTORIS');

        $this->assertSame(
            1,
            substr_count(
                $response->getContent(),
                'HISTORIS'
            )
        );
    }

    public function test_historical_period_shows_registration_total(): void
    {
        Registration::query()->create([
            'period_id' => $this->historicalPeriod->id,
            'admission_path_id' => $this->historicalPath->id,
            'major_id' => $this->major->id,
            'registration_number' => 'HIST-0001',
            'nik' => '3399999999000001',
            'full_name' => 'HISTORICAL STUDENT',
            'whatsapp' => '081299990001',
            'data_source' => 'ADMIN',
            'status' => 'REGISTERED',
            'registered_at' => now(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.historical.index'))
            ->assertOk()
            ->assertSee('1 Pendaftar');
    }

    public function test_historical_period_has_links_to_period_aware_modules(): void
    {
        $periodId = $this->historicalPeriod->id;

        $response = $this->actingAs($this->admin)
            ->get(route('admin.historical.index'));

        $response
            ->assertOk()
            ->assertSee(
                route('admin.registrations.index', [
                    'period_id' => $periodId,
                ]),
                false
            )
            ->assertSee(
                route('admin.recaps.index', [
                    'period_id' => $periodId,
                ]),
                false
            )
            ->assertSee(
                route('admin.analytics.index', [
                    'period_id' => $periodId,
                ]),
                false
            )
            ->assertSee(
                route('admin.reports.index', [
                    'period_id' => $periodId,
                ]),
                false
            );
    }

    private function makePeriod(
        string $name,
        int $yearStart,
        bool $isActive,
        string $status
    ): PpdbPeriod {
        return PpdbPeriod::query()->create([
            'school_id' => $this->school->id,
            'name' => $name,
            'year_start' => $yearStart,
            'year_end' => $yearStart + 1,
            'registration_open' => $yearStart.'-01-01',
            'registration_close' => $yearStart.'-12-31',
            'status' => $status,
            'is_active' => $isActive,
            'number_prefix' => 'MARSA',
            'number_year' => $yearStart,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 250000,
        ]);
    }
}