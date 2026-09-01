<?php

namespace Tests\Unit;

use App\Models\AdmissionPath;
use App\Models\PpdbPeriod;
use App\Models\School;
use App\Services\AdmissionPathResolver;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionPathResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_resolver_does_not_fallback_to_future_admission_path(): void
    {
        $school = School::query()->create([
            'name' => 'SMK TEST',
        ]);

        $period = PpdbPeriod::query()->create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'status' => 'OPEN',
            'is_active' => true,
            'number_year' => 2027,
        ]);

        AdmissionPath::query()->create([
            'period_id' => $period->id,
            'name' => 'Khusus',
            'code' => 'KHUSUS',
            'start_date' => '2027-01-01',
            'end_date' => '2027-03-31',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $resolver = app(AdmissionPathResolver::class);

        $this->expectException(
            ModelNotFoundException::class
        );

        $resolver->resolve(
            $period,
            now()->setDate(2026, 9, 1)
        );
    }
}