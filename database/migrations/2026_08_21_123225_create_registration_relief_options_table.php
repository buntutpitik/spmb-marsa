<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_relief_options', function (Blueprint $table) {
            $table->id();

            $table->foreignId('registration_id')
                ->constrained('registrations')
                ->cascadeOnDelete();

            $table->foreignId('relief_option_id')
                ->constrained('relief_options')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(
                ['registration_id', 'relief_option_id'],
                'registration_relief_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_relief_options');
    }
};