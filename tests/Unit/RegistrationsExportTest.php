<?php

namespace Tests\Unit;

use App\Exports\RegistrationsExport;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use Illuminate\Support\Collection;
use Tests\TestCase;

class RegistrationsExportTest extends TestCase
{
    public function test_graduation_year_heading_matches_mapped_value(): void
    {
        $export = new RegistrationsExport(new PpdbPeriod());
        $registration = new Registration([
            'origin_school' => 'SMP TEST',
            'graduation_year' => 2026,
        ]);

        $registration->setRelation('reliefOptions', new Collection());
        $registration->setRelation('specialPrograms', new Collection());

        $headings = $export->headings();         $values = $export->map($registration);
        $schoolIndex = array_search('Asal Sekolah', $headings, true);         $yearIndex = array_search('Tahun Lulus', $headings, true);
        $this->assertNotFalse($schoolIndex);         $this->assertSame($schoolIndex + 1, $yearIndex);         $this->assertCount(count($headings), $values);         $this->assertSame('SMP TEST', $values[$schoolIndex]);         $this->assertSame(2026, $values[$yearIndex]);         $registration->graduation_year = null;         $legacyValues = $export->map($registration);         $this->assertCount(count($headings), $legacyValues);         $this->assertNull($legacyValues[$yearIndex]);
    }
}
