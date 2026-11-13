<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PeriodActivationLockTest extends TestCase
{
    public function test_school_row_lock_blocks_second_connection(): void
    {
        $first = DB::connection();

        $this->assertSame(
            'ppdb_v2_test',
            $first->getDatabaseName(),
            'Test hanya boleh menggunakan ppdb_v2_test.'
        );

        $secondName = 'mysql_period_lock_test';
        $schoolId = null;

        config([
            "database.connections.{$secondName}" =>
                config('database.connections.mysql'),
        ]);

        DB::purge($secondName);
        $second = DB::connection($secondName);

        try {
            $this->assertSame(
                'ppdb_v2_test',
                $second->getDatabaseName()
            );

            $this->assertNotSame(
                $first->getPdo(),
                $second->getPdo()
            );

            $schoolId = $first->table('schools')->insertGetId([
                'name' => 'LOCK TEST '.Str::uuid(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $first->beginTransaction();

            $lockedSchool = $first
                ->table('schools')
                ->where('id', $schoolId)
                ->lockForUpdate()
                ->first();

            $this->assertNotNull($lockedSchool);

            $second->statement(
                'SET SESSION innodb_lock_wait_timeout = 1'
            );

            $second->beginTransaction();

            $blocked = false;

            try {
                $second
                    ->table('schools')
                    ->where('id', $schoolId)
                    ->lockForUpdate()
                    ->first();
            } catch (QueryException $e) {
                $blocked = (int) ($e->errorInfo[1] ?? 0) === 1205;
            }

            $this->assertTrue(
                $blocked,
                'Koneksi kedua seharusnya mengalami lock wait timeout.'
            );

            $second->rollBack();
            $first->rollBack();

            $second->beginTransaction();

            $availableSchool = $second
                ->table('schools')
                ->where('id', $schoolId)
                ->lockForUpdate()
                ->first();

            $this->assertNotNull(
                $availableSchool,
                'Lock harus tersedia setelah transaksi pertama selesai.'
            );

            $second->rollBack();
        } finally {
            if ($second->transactionLevel() > 0) {
                $second->rollBack();
            }

            if ($first->transactionLevel() > 0) {
                $first->rollBack();
            }

            if ($schoolId !== null) {
                $first->table('schools')
                    ->where('id', $schoolId)
                    ->delete();
            }

            DB::purge($secondName);
        }
    }
}
