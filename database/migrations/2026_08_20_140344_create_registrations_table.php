<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('period_id')
                ->constrained('ppdb_periods')
                ->restrictOnDelete();

            $table->foreignId('wave_id')
                ->nullable()
                ->constrained('ppdb_waves')
                ->restrictOnDelete();

            $table->foreignId('admission_path_id')
                ->constrained('admission_paths')
                ->restrictOnDelete();

            $table->foreignId('major_id')
                ->constrained('majors')
                ->restrictOnDelete();

            $table->string('registration_number', 50)->unique();

            $table->string('nik', 16);
            $table->string('nisn', 20)->nullable()->index();

            $table->string('full_name', 150);
            $table->string('birth_place', 100)->nullable();
            $table->date('birth_date')->nullable();

            $table->enum('gender', ['L', 'P'])->nullable();
            $table->string('religion', 50)->nullable();

            $table->string('origin_school', 150)->nullable();

            $table->string('hamlet', 100)->nullable();
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 10)->nullable();

            $table->string('father_name', 150)->nullable();
            $table->string('mother_name', 150)->nullable();

            $table->string('father_job', 100)->nullable();
            $table->string('mother_job', 100)->nullable();

            $table->string('whatsapp', 30);

            $table->decimal('graduation_score', 8, 2)->nullable();

            $table->text('achievement_relief')->nullable();

            $table->string('referrer_name', 150)->nullable();
            $table->string('referrer_source', 150)->nullable();

            $table->enum('data_source', [
                'PUBLIC',
                'ADMIN',
            ])->default('PUBLIC');

            $table->enum('status', [
                'REGISTERED',
                'ACCEPTED',
                'REJECTED',
                'REENROLLED',
                'WITHDRAWN',
            ])->default('REGISTERED');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('registered_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('reenrolled_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['period_id', 'nik'],
                'registrations_period_nik_unique'
            );

            $table->index(
                ['period_id', 'status'],
                'registrations_period_status_index'
            );

            $table->index(
                ['period_id', 'major_id'],
                'registrations_period_major_index'
            );

            $table->index(
                ['period_id', 'admission_path_id'],
                'registrations_period_path_index'
            );

            $table->index(
                ['period_id', 'wave_id'],
                'registrations_period_wave_index'
            );

            $table->index(
                ['period_id', 'created_at'],
                'registrations_period_created_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registrations');
    }
};