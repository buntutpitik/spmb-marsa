<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('period_relief_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ppdb_period_id')
                ->constrained('ppdb_periods')
                ->cascadeOnDelete();

            $table->foreignId('relief_option_id')
                ->constrained('relief_options')
                ->cascadeOnDelete();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['ppdb_period_id', 'relief_option_id'],
                'period_relief_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_relief_options');
    }
};