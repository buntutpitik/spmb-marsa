<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('period_special_programs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ppdb_period_id')
                ->constrained('ppdb_periods')
                ->cascadeOnDelete();

            $table->foreignId('special_program_id')
                ->constrained('special_programs')
                ->cascadeOnDelete();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['ppdb_period_id', 'special_program_id'],
                'period_special_program_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_special_programs');
    }
};