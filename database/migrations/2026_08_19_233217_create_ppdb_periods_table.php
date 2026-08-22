<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppdb_periods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained('schools')
                ->restrictOnDelete();

            $table->string('name', 20);

            $table->unsignedSmallInteger('year_start');
            $table->unsignedSmallInteger('year_end');

            $table->date('registration_open')->nullable();
            $table->date('registration_close')->nullable();

            $table->string('status', 20)->default('DRAFT');
            $table->boolean('is_active')->default(false);

            $table->string('principal_name', 150)->nullable();
            $table->string('principal_nip', 50)->nullable();

            $table->string('number_prefix', 30)->default('MARSA');
            $table->unsignedSmallInteger('number_year');
            $table->unsignedTinyInteger('number_digits')->default(4);
            $table->boolean('include_major_code')->default(true);

            $table->unsignedBigInteger('default_reenroll_fee')->default(0);

            $table->text('notes')->nullable();

            $table->timestamp('archived_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['school_id', 'name'],
                'ppdb_periods_school_name_unique'
            );

            $table->index('status');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppdb_periods');
    }
};