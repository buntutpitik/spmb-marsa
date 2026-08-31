<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\PpdbPeriod;
use App\Models\PublicPageSetting;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AdminPublicPageSettingTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_rolls_back_public_page_setting_when_activity_log_fails(): void
    {
        $school = School::query()->create([
            'name' => 'SMK PUBLIC PAGE TRANSACTION',
            'npsn' => '12345678',
        ]);

        PpdbPeriod::query()->create([
            'school_id' => $school->id,
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

        $setting = PublicPageSetting::query()->create([
            'school_id' => $school->id,
            'hero_title' => 'Judul Lama',
            'hero_subtitle' => 'Subjudul Lama',
            'show_announcement' => true,
            'show_requirements' => true,
            'show_registration_steps' => true,
            'show_reenrollment_information' => true,
            'show_contact' => true,
        ]);

        $superadmin = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        ActivityLog::creating(function () {
            throw new RuntimeException(
                'Forced public page activity log failure.'
            );
        });

        $this->actingAs($superadmin);
        $this->withoutExceptionHandling();

        try {
            $this->put(
                route('admin.public-page.update'),
                [
                    'hero_title' => 'Judul Baru',
                    'hero_subtitle' => 'Subjudul Baru',
                    'hero_description' =>
                        'Deskripsi baru yang harus rollback.',
                    'announcement_title' =>
                        'Pengumuman Baru',
                    'announcement_body' =>
                        'Isi pengumuman baru.',
                    'show_announcement' => '1',
                    'requirements' =>
                        "Persyaratan baru satu\nPersyaratan baru dua",
                    'show_requirements' => '1',
                    'registration_steps' =>
                        "Langkah baru satu\nLangkah baru dua",
                    'show_registration_steps' => '1',
                    'reenrollment_information' =>
                        'Informasi daftar ulang baru.',
                    'show_reenrollment_information' => '1',
                    'show_contact' => '1',
                ]
            );

            $this->fail(
                'Expected RuntimeException was not thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Forced public page activity log failure.',
                $exception->getMessage()
            );
        } finally {
            ActivityLog::flushEventListeners();
        }

        $setting->refresh();

        $this->assertSame(
            'Judul Lama',
            $setting->hero_title
        );

        $this->assertSame(
            'Subjudul Lama',
            $setting->hero_subtitle
        );

        $this->assertNull(
            $setting->hero_description
        );

        $this->assertDatabaseCount(
            'public_page_settings',
            1
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'UPDATE_PUBLIC_PAGE_SETTING',
            ]
        );
    }
}