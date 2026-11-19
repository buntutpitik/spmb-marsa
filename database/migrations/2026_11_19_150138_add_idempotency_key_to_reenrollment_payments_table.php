<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reenrollment_payments', function (Blueprint $table) {
            $table->uuid('idempotency_key')
                ->nullable()
                ->unique('reenrollment_payments_idempotency_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('reenrollment_payments', function (Blueprint $table) {
            $table->dropUnique(
                'reenrollment_payments_idempotency_key_unique'
            );

            $table->dropColumn('idempotency_key');
        });
    }
};
