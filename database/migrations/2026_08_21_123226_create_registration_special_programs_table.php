<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_special_programs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('registration_id')
                ->constrained('registrations')
                ->cascadeOnDelete();

            $table->foreignId('special_program_id')
                ->constrained('special_programs')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(
                ['registration_id', 'special_program_id'],
                'registration_special_program_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_special_programs');
    }
};