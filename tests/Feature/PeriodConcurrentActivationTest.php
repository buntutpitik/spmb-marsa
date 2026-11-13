<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeriodConcurrentActivationTest extends TestCase
{
    public function test_concurrent_activation_requires_isolated_testing_database(): void
    {
        $this->assertSame(
            'testing',
            app()->environment()
        );

        $this->assertSame(
            'mysql',
            DB::connection()->getDriverName()
        );

        $this->assertSame(
            'ppdb_v2_test',
            DB::connection()->getDatabaseName()
        );

        $this->assertFalse(
            app()->configurationIsCached()
        );
    }

    public function test_concurrent_activation_fixture_can_be_created_and_cleaned(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('ppdb_v2_test', DB::connection()->getDatabaseName());

        $schoolId = null;
        $periodIds = [];

        try {
            $schoolId = DB::table('schools')->insertGetId([
                'name' => 'SPMB CONCURRENCY TEST ' . bin2hex(random_bytes(4)),
                'npsn' => (string) random_int(10000000, 99999999),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ([2035, 2036] as $year) {
                $periodIds[] = DB::table('ppdb_periods')->insertGetId([
                    'school_id' => $schoolId,
                    'name' => $year . '/' . ($year + 1),
                    'year_start' => $year,
                    'year_end' => $year + 1,
                    'registration_open' => $year . '-01-01',
                    'registration_close' => $year . '-06-30',
                    'status' => 'OPEN',
                    'is_active' => false,
                    'number_prefix' => 'TEST',
                    'number_year' => $year,
                    'number_digits' => 4,
                    'include_major_code' => true,
                    'default_reenroll_fee' => 250000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->assertCount(2, $periodIds);

            $this->assertSame(
                2,
                DB::table('ppdb_periods')
                    ->where('school_id', $schoolId)
                    ->where('status', 'OPEN')
                    ->count()
            );

            $this->assertSame(
                0,
                DB::table('ppdb_periods')
                    ->where('school_id', $schoolId)
                    ->where('is_active', true)
                    ->count()
            );
        } finally {
            if ($periodIds !== []) {
                DB::table('ppdb_periods')
                    ->whereIn('id', $periodIds)
                    ->delete();
            }

            if ($schoolId !== null) {
                DB::table('schools')
                    ->where('id', $schoolId)
                    ->delete();
            }
        }

        $this->assertSame(
            0,
            DB::table('ppdb_periods')
                ->whereIn('id', $periodIds)
                ->count()
        );

        $this->assertSame(
            0,
            DB::table('schools')
                ->where('id', $schoolId)
                ->count()
        );
    }

    public function test_concurrent_activation_superadmin_fixture_can_be_cleaned(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('ppdb_v2_test', DB::connection()->getDatabaseName());

        $userId = null;

        try {
            $user = \App\Models\User::factory()->create([
                'role' => 'SUPERADMIN',
                'is_active' => true,
            ]);

            $userId = $user->id;

            $this->assertTrue($user->isSuperadmin());
            $this->assertTrue($user->is_active);

            $this->assertDatabaseHas('users', [
                'id' => $userId,
                'role' => 'SUPERADMIN',
                'is_active' => true,
            ]);
        } finally {
            if ($userId !== null) {
                DB::table('activity_logs')
                    ->where('user_id', $userId)
                    ->delete();

                DB::table('users')
                    ->where('id', $userId)
                    ->delete();
            }
        }

        if ($userId !== null) {
            $this->assertDatabaseMissing('users', [
                'id' => $userId,
            ]);
        }
    }

    public function test_child_process_can_authenticate_superadmin(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('ppdb_v2_test', DB::connection()->getDatabaseName());

        $userId = null;

        try {
            $user = \App\Models\User::factory()->create([
                'role' => 'SUPERADMIN',
                'is_active' => true,
            ]);

            $userId = $user->id;

            $script = '$user = \App\Models\User::findOrFail('
                . $userId
                . '); '
                . '\Illuminate\Support\Facades\Auth::login($user); '
                . 'dump(['
                . '"database" => \Illuminate\Support\Facades\DB::connection()->getDatabaseName(), '
                . '"authenticated" => auth()->check(), '
                . '"role" => auth()->user()?->role'
                . ']);';

            $process = new \Symfony\Component\Process\Process(
                [
                    PHP_BINARY,
                    'artisan',
                    '--env=testing',
                    'tinker',
                    '--execute=' . $script,
                ],
                base_path(),
                [
                    'APP_ENV' => 'testing',
                    'DB_CONNECTION' => 'mysql',
                    'DB_DATABASE' => 'ppdb_v2_test',
                    'DB_URL' => '',
                ]
            );

            $process->mustRun();

            $this->assertStringContainsString(
                'ppdb_v2_test',
                $process->getOutput()
            );

            $this->assertStringContainsString(
                '"authenticated" => true',
                $process->getOutput()
            );

            $this->assertStringContainsString(
                '"role" => "SUPERADMIN"',
                $process->getOutput()
            );
        } finally {
            if ($userId !== null) {
                DB::table('activity_logs')
                    ->where('user_id', $userId)
                    ->delete();

                DB::table('users')
                    ->where('id', $userId)
                    ->delete();
            }
        }

        if ($userId !== null) {
            $this->assertDatabaseMissing('users', [
                'id' => $userId,
            ]);
        }
    }

    public function test_child_process_can_send_authenticated_period_update_request(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('ppdb_v2_test', DB::connection()->getDatabaseName());

        $userId = null;

        try {
            $user = \App\Models\User::factory()->create([
                'role' => 'SUPERADMIN',
                'is_active' => true,
            ]);

            $userId = $user->id;

            $script = <<<'PHP'
    $user = \App\Models\User::findOrFail(USER_ID);
    \Illuminate\Support\Facades\Auth::login($user);

    $request = \Illuminate\Http\Request::create(
        '/admin/pengaturan/periode/999999999',
        'PUT'
    );

    $response = app()->handle($request);

    dump([
        'database' => \Illuminate\Support\Facades\DB::connection()->getDatabaseName(),
        'status' => $response->getStatusCode(),
    ]);
    PHP;

            $script = str_replace(
                'USER_ID',
                (string) $userId,
                $script
            );

            $process = new \Symfony\Component\Process\Process(
                [
                    PHP_BINARY,
                    'artisan',
                    '--env=testing',
                    'tinker',
                    '--execute=' . $script,
                ],
                base_path(),
                [
                    'APP_ENV' => 'testing',
                    'DB_CONNECTION' => 'mysql',
                    'DB_DATABASE' => 'ppdb_v2_test',
                    'DB_URL' => '',
                ]
            );

            $process->setTimeout(30);
            $process->mustRun();

            $this->assertStringContainsString(
                '"database" => "ppdb_v2_test"',
                $process->getOutput()
            );

            $this->assertStringContainsString(
                '"status" => 404',
                $process->getOutput()
            );
        } finally {
            if ($userId !== null) {
                DB::table('activity_logs')
                    ->where('user_id', $userId)
                    ->delete();

                DB::table('users')
                    ->where('id', $userId)
                    ->delete();
            }
        }
    }

    public function test_two_concurrent_period_activations_keep_only_one_active(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('ppdb_v2_test', DB::connection()->getDatabaseName());

        $schoolId = null;
        $userId = null;
        $periodIds = [];
        $processes = [];
        $locked = false;

        $directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'spmb-period-' . bin2hex(random_bytes(8));

        if (!mkdir($directory, 0700, true)) {
            throw new \RuntimeException('Cannot create test directory.');
        }

        try {
            $schoolId = DB::table('schools')->insertGetId([
                'name' => 'CONCURRENT PERIOD TEST ' . bin2hex(random_bytes(4)),
                'npsn' => (string) random_int(10000000, 99999999),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $user = \App\Models\User::factory()->create([
                'role' => 'SUPERADMIN',
                'is_active' => true,
            ]);

            $userId = $user->id;

            foreach ([2035, 2036] as $year) {
                $periodIds[] = DB::table('ppdb_periods')->insertGetId([
                    'school_id' => $schoolId,
                    'name' => $year . '/' . ($year + 1),
                    'year_start' => $year,
                    'year_end' => $year + 1,
                    'registration_open' => $year . '-01-01',
                    'registration_close' => $year . '-06-30',
                    'status' => 'OPEN',
                    'is_active' => false,
                    'number_prefix' => 'TEST',
                    'number_year' => $year,
                    'number_digits' => 4,
                    'include_major_code' => true,
                    'default_reenroll_fee' => 250000,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($periodIds as $index => $periodId) {
                $year = 2035 + $index;

                $payload = [
                    'name' => $year . '/' . ($year + 1),
                    'year_start' => $year,
                    'year_end' => $year + 1,
                    'registration_open' => $year . '-01-01',
                    'registration_close' => $year . '-06-30',
                    'status' => 'OPEN',
                    'is_active' => '1',
                    'principal_name' => 'KEPALA SEKOLAH TEST',
                    'principal_nip' => '123456789',
                    'number_prefix' => 'TEST',
                    'number_year' => $year,
                    'number_digits' => 4,
                    'include_major_code' => '1',
                    'default_reenroll_fee' => 250000,
                    'notes' => null,
                ];

                file_put_contents(
                    $directory . DIRECTORY_SEPARATOR . "payload-$index.json",
                    json_encode($payload, JSON_THROW_ON_ERROR)
                );
            }

            DB::beginTransaction();
            $locked = true;

            DB::table('schools')
                ->where('id', $schoolId)
                ->lockForUpdate()
                ->first();

            foreach ($periodIds as $index => $periodId) {
                $process = new \Symfony\Component\Process\Process(
                    [
                        PHP_BINARY,
                        base_path('tests/Support/period_activation_worker.php'),
                        (string) $userId,
                        (string) $periodId,
                        $directory . DIRECTORY_SEPARATOR . "payload-$index.json",
                        $directory . DIRECTORY_SEPARATOR . "result-$index.json",
                    ],
                    base_path(),
                    [
                        'APP_ENV' => 'testing',
                        'DB_CONNECTION' => 'mysql',
                        'DB_DATABASE' => 'ppdb_v2_test',
                        'DB_URL' => '',
                    ]
                );

                $process->setTimeout(30);
                $process->start();
                $processes[] = $process;
            }

            $parentConnectionId = (int) DB::selectOne(
                'SELECT CONNECTION_ID() AS id'
            )->id;

            $deadline = microtime(true) + 15;
            $waitingConnections = [];

            do {
                $waitingConnections = DB::select(
                    <<<'SQL'
                    SELECT DISTINCT
                        waiting_trx.trx_mysql_thread_id AS connection_id
                    FROM information_schema.INNODB_LOCK_WAITS AS waits
                    INNER JOIN information_schema.INNODB_TRX AS waiting_trx
                        ON waiting_trx.trx_id = waits.requesting_trx_id
                    INNER JOIN information_schema.INNODB_TRX AS blocking_trx
                        ON blocking_trx.trx_id = waits.blocking_trx_id
                    WHERE blocking_trx.trx_mysql_thread_id = ?
                    SQL,
                    [$parentConnectionId]
                );

                if (count($waitingConnections) >= 2) {
                    break;
                }

                foreach ($processes as $index => $process) {
                    if (!$process->isRunning()) {
                        $this->fail(
                            "Worker {$index} exited before acquiring lock: "
                            . $process->getErrorOutput()
                            . $process->getOutput()
                        );
                    }
                }

                usleep(100000);
            } while (microtime(true) < $deadline);

            $this->assertCount(
                2,
                $waitingConnections,
                'Both workers must wait for the parent school row lock.'
            );

            DB::commit();
            $locked = false;

            foreach ($processes as $process) {
                $process->wait();
                $this->assertSame(
                    0,
                    $process->getExitCode(),
                    $process->getErrorOutput()
                );
            }

            foreach ([0, 1] as $index) {
                $result = json_decode(
                    file_get_contents(
                        $directory . DIRECTORY_SEPARATOR . "result-$index.json"
                    ),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                );

                $this->assertSame('ppdb_v2_test', $result['database']);
                $this->assertSame(302, $result['http_status']);
            }

            $this->assertSame(
                1,
                DB::table('ppdb_periods')
                    ->where('school_id', $schoolId)
                    ->where('is_active', true)
                    ->count()
            );

            $this->assertSame(
                2,
                DB::table('activity_logs')
                    ->where('user_id', $userId)
                    ->where('action', 'UPDATE_PPDB_PERIOD')
                    ->count()
            );
        } finally {
            if ($locked) {
                DB::rollBack();
            }

            foreach ($processes as $process) {
                if ($process->isRunning()) {
                    $process->stop(1);
                }
            }

            if ($userId !== null) {
                DB::table('activity_logs')
                    ->where('user_id', $userId)
                    ->delete();
            }

            if ($periodIds !== []) {
                DB::table('ppdb_periods')
                    ->whereIn('id', $periodIds)
                    ->delete();
            }

            if ($schoolId !== null) {
                DB::table('schools')
                    ->where('id', $schoolId)
                    ->delete();
            }

            if ($userId !== null) {
                DB::table('users')
                    ->where('id', $userId)
                    ->delete();
            }

            foreach (glob($directory . DIRECTORY_SEPARATOR . '*.json') ?: [] as $file) {
                unlink($file);
            }

            rmdir($directory);
        }
    }
}
