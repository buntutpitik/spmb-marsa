<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reenrollment_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('registration_id')
                ->constrained('registrations')
                ->restrictOnDelete();

            $table->unsignedBigInteger('amount');

            $table->timestamp('paid_at')->useCurrent();

            $table->string('payment_method', 30)->nullable();
            $table->string('reference_number', 100)->nullable();

            $table->foreignId('received_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(
                ['registration_id', 'paid_at'],
                'reenrollment_payments_registration_paid_index'
            );

            $table->index(
                ['received_by', 'paid_at'],
                'reenrollment_payments_receiver_paid_index'
            );

            $table->index(
                'reference_number',
                'reenrollment_payments_reference_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reenrollment_payments');
    }
};