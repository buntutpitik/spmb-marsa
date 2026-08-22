<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ppdb_waves', function (Blueprint $table) {
            $table->id();

            $table->foreignId('period_id')
                ->constrained('ppdb_periods')
                ->restrictOnDelete();

            $table->string('name', 100);
            $table->string('code', 30);

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->unsignedBigInteger('registration_fee')->default(0);
            $table->unsignedBigInteger('reenroll_fee')->nullable();

            $table->unsignedInteger('quota')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_legacy')->default(false);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['period_id', 'code'],
                'ppdb_waves_period_code_unique'
            );

            $table->index(
                ['period_id', 'is_active'],
                'ppdb_waves_period_active_index'
            );

            $table->index(
                ['period_id', 'start_date', 'end_date'],
                'ppdb_waves_period_dates_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ppdb_waves');
    }
};