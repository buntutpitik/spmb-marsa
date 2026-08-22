<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('registration_id')
                ->nullable()
                ->constrained('registrations')
                ->nullOnDelete();

            $table->string('phone', 30);

            $table->string('message_type', 50)->nullable();

            $table->text('message');

            $table->string('provider', 50)->default('META_CLOUD');

            $table->enum('status', [
                'PENDING',
                'SUCCESS',
                'FAILED',
            ])->default('PENDING');

            $table->string('provider_message_id', 150)->nullable();

            $table->longText('response')->nullable();

            $table->text('error_message')->nullable();

            $table->unsignedInteger('attempt_count')->default(0);

            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();

            $table->timestamps();

            $table->index(
                ['registration_id', 'created_at'],
                'whatsapp_logs_registration_created_index'
            );

            $table->index(
                ['status', 'created_at'],
                'whatsapp_logs_status_created_index'
            );

            $table->index(
                ['phone', 'created_at'],
                'whatsapp_logs_phone_created_index'
            );

            $table->index(
                ['provider', 'status'],
                'whatsapp_logs_provider_status_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};