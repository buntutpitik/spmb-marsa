<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('registration_id')
                ->constrained('registrations')
                ->cascadeOnDelete();

            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);

            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('changed_at')->useCurrent();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(
                ['registration_id', 'changed_at'],
                'registration_status_histories_registration_changed_index'
            );

            $table->index(
                ['registration_id', 'to_status'],
                'registration_status_histories_registration_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_status_histories');
    }
};