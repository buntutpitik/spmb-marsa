<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRegistrationFormUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_form_has_consistent_public_ui_structure(): void
    {
        $school = School::query()->create([
            'name' => 'SMK PUBLIC REGISTRATION TEST',
            'npsn' => '20300001',
        ]);

        $period = PpdbPeriod::query()->create([
            'school_id' => $school->id,
            'name' => '2027/2028',
            'year_start' => 2027,
            'year_end' => 2028,
            'registration_open' => now()->subDay()->toDateString(),
            'registration_close' => now()->addMonth()->toDateString(),
            'status' => 'OPEN',
            'is_active' => true,
            'number_prefix' => 'MARSA',
            'number_year' => 2027,
            'number_digits' => 4,
            'include_major_code' => true,
            'default_reenroll_fee' => 0,
        ]);

        $major = Major::query()->create([
            'school_id' => $school->id,
            'name' => 'Rekayasa Perangkat Lunak',
            'code' => 'RPL',
            'capacity' => 100,
            'is_active' => true,
        ]);

        $period->majors()->attach($major->id, [
            'is_active' => true,
        ]);

        $path = AdmissionPath::query()->create([
            'period_id' => $period->id,
            'name' => 'Umum',
            'code' => 'UMUM',
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->get(route('registration.create'))
            ->assertOk()
            ->assertSee('Kembali ke Beranda')
            ->assertSee('Formulir Pendaftaran')
            ->assertSee('Pilihan Pendaftaran')
            ->assertSee('Identitas Calon Siswa')
            ->assertSee('Alamat')
            ->assertSee('Orang Tua & Kontak', false)
            ->assertSee('Informasi Tambahan')
            ->assertSee('Kirim Pendaftaran')
            ->assertDontSee('name="referrer_name"', false)
            ->assertDontSee('name="referrer_source"', false)
            ->assertDontSee('name="relief_options', false);
    }
}
