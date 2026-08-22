<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('period_majors', function (Blueprint $table) {
            $table->id();

            $table->foreignId('period_id')
                ->constrained('ppdb_periods')
                ->restrictOnDelete();

            $table->foreignId('major_id')
                ->constrained('majors')
                ->restrictOnDelete();

            $table->unsignedInteger('quota')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['period_id', 'major_id'],
                'period_majors_period_major_unique'
            );

            $table->index(['period_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('period_majors');
    }
};