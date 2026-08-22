<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('registration_id')
                ->nullable()
                ->constrained('registrations')
                ->nullOnDelete();

            $table->string('action', 100);

            $table->text('description')->nullable();

            $table->json('metadata')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['registration_id', 'created_at'],
                'activity_logs_registration_created_index'
            );

            $table->index(
                ['user_id', 'created_at'],
                'activity_logs_user_created_index'
            );

            $table->index(
                ['action', 'created_at'],
                'activity_logs_action_created_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};