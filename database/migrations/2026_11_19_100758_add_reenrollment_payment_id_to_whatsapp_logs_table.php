<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->foreignId('reenrollment_payment_id')->nullable()->constrained('reenrollment_payments')->restrictOnDelete();
        });
        DB::statement('ALTER TABLE whatsapp_logs ADD COLUMN legacy_message_key VARCHAR(50) GENERATED ALWAYS AS (CASE WHEN reenrollment_payment_id IS NULL THEN message_type ELSE NULL END) PERSISTENT');
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->unique(['registration_id', 'legacy_message_key'], 'wa_logs_legacy_message_unique');
            $table->unique(['reenrollment_payment_id', 'message_type'], 'wa_logs_payment_message_unique');
            $table->dropUnique('wa_logs_registration_message_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('whatsapp_logs')->whereNotNull('reenrollment_payment_id')->exists()) {
            throw new \RuntimeException('Rollback ditolak: log pembayaran masih tersedia.');
        }
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->unique(['registration_id', 'message_type'], 'wa_logs_registration_message_unique');
            $table->dropUnique('wa_logs_payment_message_unique');
            $table->dropUnique('wa_logs_legacy_message_unique');
        });
        DB::statement('ALTER TABLE whatsapp_logs DROP COLUMN legacy_message_key');
        Schema::table('whatsapp_logs', function (Blueprint $table) {
            $table->dropForeign(['reenrollment_payment_id']);
            $table->dropColumn('reenrollment_payment_id');
        });
    }
};
