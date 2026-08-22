<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_sequences', function (Blueprint $table) {
            $table->id();

            $table->foreignId('period_id')
                ->constrained('ppdb_periods')
                ->restrictOnDelete();

            $table->foreignId('major_id')
                ->nullable()
                ->constrained('majors')
                ->restrictOnDelete();

            $table->string('sequence_key', 50);

            $table->unsignedBigInteger('current_number')->default(0);

            $table->timestamps();

            $table->unique(
                ['period_id', 'sequence_key'],
                'registration_sequences_period_key_unique'
            );

            $table->index(
                ['period_id', 'major_id'],
                'registration_sequences_period_major_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_sequences');
    }
};