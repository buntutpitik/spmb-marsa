<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_paths', function (Blueprint $table) {
            $table->id();

            $table->foreignId('period_id')
                ->constrained('ppdb_periods')
                ->restrictOnDelete();

            $table->string('name', 100);
            $table->string('code', 30);

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->text('description')->nullable();

            $table->timestamps();

            $table->unique(
                ['period_id', 'code'],
                'admission_paths_period_code_unique'
            );

            $table->index(['period_id', 'is_active']);
            $table->index(['period_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_paths');
    }
};