<?php

namespace Tests\Feature;

use App\Models\PpdbPeriod;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AdminReportExportPeriodContextTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    private PpdbPeriod $archivedPeriod;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'ADMIN',
            'is_active' => true,
        ]);

        $school = School::create([
            'name' => 'SMK REPORT PERIOD CONTEXT TEST',
        ]);

        $this->archivedPeriod = PpdbPeriod::create([
            'school_id' => $school->id,
            'name' => '2025/2026',
            'year_start' => 2025,
            'year_end' => 2026,
            'status' => 'CLOSED',
            'is_active' => false,
            'number_year' => 2025,
            'archived_at' => now(),
        ]);
    }

    public function test_archived_period_returns_404_across_report_exports(): void
    {
        $routes = [
            'admin.reports.registrations.excel',
            'admin.reports.registrations.pdf',

            'admin.reports.admissions.excel',
            'admin.reports.admissions.pdf',

            'admin.reports.major-recap.excel',
            'admin.reports.major-recap.pdf',

            'admin.reports.origin-school-recap.excel',
            'admin.reports.origin-school-recap.pdf',

            'admin.reports.referral-recap.excel',
            'admin.reports.referral-recap.pdf',

            'admin.reports.reenrollment-finance.excel',
            'admin.reports.reenrollment-finance.pdf',
        ];

        foreach ($routes as $routeName) {
            $this
                ->actingAs($this->admin)
                ->get(
                    route($routeName, [
                        'period_id' => $this->archivedPeriod->id,
                    ])
                )
                ->assertNotFound();
        }
    }

    public function test_invalid_period_returns_404_across_report_exports(): void
    {
        $routes = [
            'admin.reports.registrations.excel',
            'admin.reports.registrations.pdf',

            'admin.reports.admissions.excel',
            'admin.reports.admissions.pdf',

            'admin.reports.major-recap.excel',
            'admin.reports.major-recap.pdf',

            'admin.reports.origin-school-recap.excel',
            'admin.reports.origin-school-recap.pdf',

            'admin.reports.referral-recap.excel',
            'admin.reports.referral-recap.pdf',

            'admin.reports.reenrollment-finance.excel',
            'admin.reports.reenrollment-finance.pdf',
        ];

        foreach ($routes as $routeName) {
            $this
                ->actingAs($this->admin)
                ->get(
                    route($routeName, [
                        'period_id' => 999999,
                    ])
                )
                ->assertNotFound();
        }
    }

    public function test_missing_period_returns_404_across_report_exports(): void
    {
        $routes = [
            'admin.reports.registrations.excel',
            'admin.reports.registrations.pdf',

            'admin.reports.admissions.excel',
            'admin.reports.admissions.pdf',

            'admin.reports.major-recap.excel',
            'admin.reports.major-recap.pdf',

            'admin.reports.origin-school-recap.excel',
            'admin.reports.origin-school-recap.pdf',

            'admin.reports.referral-recap.excel',
            'admin.reports.referral-recap.pdf',

            'admin.reports.reenrollment-finance.excel',
            'admin.reports.reenrollment-finance.pdf',
        ];

        foreach ($routes as $routeName) {
            $this
                ->actingAs($this->admin)
                ->get(route($routeName))
                ->assertNotFound();
        }
    }
}