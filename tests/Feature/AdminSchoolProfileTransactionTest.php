<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\PpdbPeriod;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class AdminSchoolProfileTransactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_rolls_back_database_and_new_branding_files_when_activity_log_fails(): void
    {
        Storage::fake('public');

        $school = School::query()->create([
            'name' => 'SMK SCHOOL PROFILE TRANSACTION',
            'npsn' => '12345678',
            'address' => 'Alamat Lama',
            'village' => 'Desa Lama',
            'district' => 'Kecamatan Lama',
            'city' => 'Kebumen',
            'province' => 'Jawa Tengah',
            'postal_code' => '54311',
            'phone' => '0287123456',
            'whatsapp' => '081234567890',
            'email' => 'lama@example.test',
            'website' => 'https://lama.example.test',
            'logo_path' => null,
            'favicon_path' => null,
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

        $oldLogoPath =
            "schools/{$school->id}/branding/old-logo.png";

        $oldFaviconPath =
            "schools/{$school->id}/branding/old-favicon.png";

        Storage::disk('public')->put(
            $oldLogoPath,
            'old-logo'
        );

        Storage::disk('public')->put(
            $oldFaviconPath,
            'old-favicon'
        );

        $school->update([
            'logo_path' => $oldLogoPath,
            'favicon_path' => $oldFaviconPath,
        ]);

        $user = User::factory()->create([
            'role' => 'SUPERADMIN',
            'is_active' => true,
        ]);

        $newLogo = UploadedFile::fake()->image(
            'new-logo.png',
            500,
            500
        );

        $newFavicon = UploadedFile::fake()->image(
            'new-favicon.png',
            64,
            64
        );

        ActivityLog::creating(function () {
            throw new RuntimeException(
                'Forced school profile activity log failure.'
            );
        });

        $this->actingAs($user);
        $this->withoutExceptionHandling();

        try {
            $this->put(
                route('admin.school-profile.update'),
                [
                    'name' => 'SHOULD ROLLBACK',
                    'npsn' => '87654321',
                    'address' => 'Alamat Baru',
                    'village' => 'Desa Baru',
                    'district' => 'Kecamatan Baru',
                    'city' => 'Kebumen Baru',
                    'province' => 'Jawa Tengah',
                    'postal_code' => '54321',
                    'phone' => '0287999999',
                    'whatsapp' => '081299999999',
                    'email' => 'baru@example.test',
                    'website' =>
                        'https://baru.example.test',
                    'logo' => $newLogo,
                    'favicon' => $newFavicon,
                ]
            );

            $this->fail(
                'Expected RuntimeException was not thrown.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Forced school profile activity log failure.',
                $exception->getMessage()
            );
        } finally {
            ActivityLog::flushEventListeners();
        }

        $school->refresh();

        /*
         * Database harus kembali ke kondisi lama.
         */
        $this->assertSame(
            'SMK SCHOOL PROFILE TRANSACTION',
            $school->name
        );

        $this->assertSame(
            '12345678',
            $school->npsn
        );

        $this->assertSame(
            $oldLogoPath,
            $school->logo_path
        );

        $this->assertSame(
            $oldFaviconPath,
            $school->favicon_path
        );

        /*
         * File lama tidak boleh ikut terhapus.
         */
        Storage::disk('public')
            ->assertExists($oldLogoPath);

        Storage::disk('public')
            ->assertExists($oldFaviconPath);

        /*
         * File baru yang sempat di-upload harus dibersihkan.
         *
         * Karena nama hasil store() bersifat generated,
         * verifikasi isi folder harus kembali hanya berisi
         * dua file lama.
         */
        $files = Storage::disk('public')->allFiles(
            "schools/{$school->id}/branding"
        );

        sort($files);

        $expectedFiles = [
            $oldFaviconPath,
            $oldLogoPath,
        ];

        sort($expectedFiles);

        $this->assertSame(
            $expectedFiles,
            $files
        );

        $this->assertDatabaseMissing(
            'activity_logs',
            [
                'action' =>
                    'UPDATE_SCHOOL_PROFILE',
            ]
        );
    }
}