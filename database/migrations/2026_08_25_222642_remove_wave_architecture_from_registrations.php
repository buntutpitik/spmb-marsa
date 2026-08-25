<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * ---------------------------------------------------------
         * Lepaskan registrations dari arsitektur gelombang.
         * ---------------------------------------------------------
         */
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropForeign(
                'registrations_wave_id_foreign'
            );

            $table->dropIndex(
                'registrations_period_wave_index'
            );

            $table->dropColumn('wave_id');
        });

        /*
         * ---------------------------------------------------------
         * Hapus tabel gelombang.
         * ---------------------------------------------------------
         */
        Schema::dropIfExists('ppdb_waves');
    }

    public function down(): void
    {
        /*
         * ---------------------------------------------------------
         * Pulihkan tabel ppdb_waves sesuai migration aslinya.
         * ---------------------------------------------------------
         */
        Schema::create('ppdb_waves', function (Blueprint $table) {
            $table->id();

            $table->foreignId('period_id')
                ->constrained('ppdb_periods')
                ->restrictOnDelete();

            $table->string('name', 100);
            $table->string('code', 30);

            $table->date('start_date')
                ->nullable();

            $table->date('end_date')
                ->nullable();

            $table->unsignedBigInteger(
                'registration_fee'
            )->default(0);

            $table->unsignedBigInteger(
                'reenroll_fee'
            )->nullable();

            $table->unsignedInteger(
                'quota'
            )->nullable();

            $table->boolean('is_active')
                ->default(true);

            $table->boolean('is_legacy')
                ->default(false);

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'period_id',
                    'code',
                ],
                'ppdb_waves_period_code_unique'
            );

            $table->index(
                [
                    'period_id',
                    'is_active',
                ],
                'ppdb_waves_period_active_index'
            );

            $table->index(
                [
                    'period_id',
                    'start_date',
                    'end_date',
                ],
                'ppdb_waves_period_dates_index'
            );
        });

        /*
         * ---------------------------------------------------------
         * Pulihkan wave_id pada registrations.
         * ---------------------------------------------------------
         */
        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('wave_id')
                ->nullable()
                ->after('period_id');

            $table->foreign('wave_id')
                ->references('id')
                ->on('ppdb_waves')
                ->restrictOnDelete();

            $table->index(
                [
                    'period_id',
                    'wave_id',
                ],
                'registrations_period_wave_index'
            );
        });
    }
};