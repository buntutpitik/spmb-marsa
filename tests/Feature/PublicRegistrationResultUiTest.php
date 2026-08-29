<?php

namespace Tests\Feature;

use App\Models\AdmissionPath;
use App\Models\Major;
use App\Models\PpdbPeriod;
use App\Models\Registration;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicRegistrationResultUiTest extends TestCase
{
    use RefreshDatabase;

    private School $school;
    private PpdbPeriod $period;
    private AdmissionPath $admissionPath;
    private Major $major;
    private Registration $registration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->school = School::query()->create([
            'name' => 'SMK PUBLIC RESULT TEST',
            'npsn' => '20300002',
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

        $this->registration = Registration::query()->create([
            'period_id' => $this->period->id,
            'admission_path_id' => $this->admissionPath->id,
            'major_id' => $this->major->id,
            'registration_number' => 'MARSA-2027-RPL-0001',
            'nik' => '3377777777000021',
            'full_name' => 'SISWA HASIL PUBLIK',
            'whatsapp' => '081277770021',
            'data_source' => 'PUBLIC',
            'status' => 'REGISTERED',
            'registered_at' => now(),
        ]);

        $this->registration->forceFill([
            'public_token' => '01HRESULTPUBLICUITOKEN001',
        ])->save();

        $this->registration->refresh();
    }

    public function test_success_page_has_consistent_public_result_ui(): void
    {
        $this->get(route(
            'registration.success',
            $this->registration->public_token
        ))
            ->assertOk()
            ->assertSee($this->school->name)
            ->assertSee('Kembali ke Beranda')
            ->assertSee('Pendaftaran Berhasil')
            ->assertSee($this->registration->registration_number)
            ->assertSee('Simpan akses pendaftaran Anda')
            ->assertSee('Cek Status Pendaftaran')
            ->assertSee('Cetak Kartu Pendaftaran')
            ->assertSee(
                route(
                    'registration.status',
                    $this->registration->public_token
                ),
                false
            )
            ->assertSee(
                route(
                    'registration.card',
                    $this->registration->public_token
                ),
                false
            );
    }

    public function test_status_page_has_consistent_public_result_ui(): void
    {
        $this->get(route(
            'registration.status',
            $this->registration->public_token
        ))
            ->assertOk()
            ->assertSee($this->school->name)
            ->assertSee('Kembali ke Beranda')
            ->assertSee('Status Pendaftaran')
            ->assertSee('Status Saat Ini')
            ->assertSee('Terdaftar')
            ->assertSee($this->registration->registration_number)
            ->assertSee('Cetak Kartu Pendaftaran')
            ->assertSee(
                route(
                    'registration.card',
                    $this->registration->public_token
                ),
                false
            );
    }

    public function test_result_pages_do_not_expose_sensitive_registration_fields(): void
    {
        $this->registration->update([
            'nisn' => '1122334455',
            'father_name' => 'AYAH PRIVATE RESULT',
            'mother_name' => 'IBU PRIVATE RESULT',
            'referrer_name' => 'REFERRER PRIVATE RESULT',
            'referrer_source' => 'SOURCE PRIVATE RESULT',
            'notes' => 'NOTES PRIVATE RESULT',
        ]);

        foreach ([
            'registration.success',
            'registration.status',
        ] as $routeName) {
            $this->get(route(
                $routeName,
                $this->registration->public_token
            ))
                ->assertOk()
                ->assertDontSee('3377777777000021')
                ->assertDontSee('1122334455')
                ->assertDontSee('081277770021')
                ->assertDontSee('AYAH PRIVATE RESULT')
                ->assertDontSee('IBU PRIVATE RESULT')
                ->assertDontSee('REFERRER PRIVATE RESULT')
                ->assertDontSee('SOURCE PRIVATE RESULT')
                ->assertDontSee('NOTES PRIVATE RESULT');
        }
    }

    public function test_result_pages_remain_available_for_closed_period(): void
    {
        $this->period->update([
            'status' => 'CLOSED',
            'is_active' => false,
        ]);

        $this->get(route(
            'registration.success',
            $this->registration->public_token
        ))
            ->assertOk()
            ->assertSee($this->registration->registration_number);

        $this->get(route(
            'registration.status',
            $this->registration->public_token
        ))
            ->assertOk()
            ->assertSee($this->registration->registration_number);
    }
}