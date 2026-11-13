<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PeriodConcurrentCreationTest extends TestCase
{
    public function test_two_concurrent_requests_create_only_one_period(): void
    {
        $this->assertSame('testing', app()->environment());
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame('ppdb_v2_test', DB::connection()->getDatabaseName());
        $this->assertFalse(app()->configurationIsCached());

        $schoolId = null;
        $userId = null;
        $processes = [];
        $locked = false;

        $directory = sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'spmb-creation-' . bin2hex(random_bytes(8));

        if (!mkdir($directory, 0700, true)) {
            throw new \RuntimeException('Cannot create test directory.');
        }

        try {
            // Aplikasi menggunakan satu sekolah.
            $this->assertSame(0, DB::table('schools')->count());

            $schoolId = DB::table('schools')->insertGetId([
                'name' => 'CONCURRENT CREATION TEST',
                'npsn' => (string) random_int(10000000, 99999999),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $user = User::factory()->create([
                'role' => 'SUPERADMIN',
                'is_active' => true,
            ]);

            $userId = $user->id;

            $payload = [
                'name' => '2038/2039',
                'year_start' => 2038,
                'year_end' => 2039,
                'registration_open' => '2038-01-01',
                'registration_close' => '2038-06-30',
                'number_prefix' => 'TEST',
                'number_year' => 2038,
                'number_digits' => 4,
                'include_major_code' => '1',
                'default_reenroll_fee' => 250000,
            ];

            foreach ([0, 1] as $index) {
                file_put_contents(
                    $directory . DIRECTORY_SEPARATOR . "payload-$index.json",
                    json_encode($payload, JSON_THROW_ON_ERROR)
                );
            }

            // Induk menahan lock sekolah.
            DB::beginTransaction();
            $locked = true;

            DB::table('schools')
                ->where('id', $schoolId)
                ->lockForUpdate()
                ->first();

            foreach ([0, 1] as $index) {
                $process = new Process(
                    [
                        PHP_BINARY,
                        base_path('tests/Support/period_creation_worker.php'),
                        (string) $userId,
                        $directory . DIRECTORY_SEPARATOR . "payload-$index.json",
                        $directory . DIRECTORY_SEPARATOR . "result-$index.json",
                    ],
                    base_path(),
                    [
                        'APP_ENV' => 'testing',
                        'DB_CONNECTION' => 'mysql',
                        'DB_DATABASE' => 'ppdb_v2_test',
                        'DB_URL' => '',
                        'SESSION_DRIVER' => 'array',
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
                            "Worker {$index} exited early: "
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
                'Both creation workers must wait for the school lock.'
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
                $resultFile = $directory
                    . DIRECTORY_SEPARATOR
                    . "result-$index.json";

                $this->assertFileExists(
                    $resultFile,
                    "Worker {$index} did not write its result."
                    . "\nSTDOUT:\n" . $processes[$index]->getOutput()
                    . "\nSTDERR:\n" . $processes[$index]->getErrorOutput()
                );
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
                    ->where('year_start', 2038)
                    ->where('year_end', 2039)
                    ->count()
            );

            $this->assertSame(
                1,
                DB::table('activity_logs')
                    ->where('user_id', $userId)
                    ->where('action', 'CREATE_PPDB_PERIOD')
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

            if ($schoolId !== null) {
                DB::table('ppdb_periods')
                    ->where('school_id', $schoolId)
                    ->delete();
            }

            if ($userId !== null) {
                DB::table('users')
                    ->where('id', $userId)
                    ->delete();
            }

            if ($schoolId !== null) {
                DB::table('schools')
                    ->where('id', $schoolId)
                    ->delete();
            }

            foreach (
                glob($directory . DIRECTORY_SEPARATOR . '*.json') ?: []
                as $file
            ) {
                unlink($file);
            }

            rmdir($directory);
        }
    }
}
