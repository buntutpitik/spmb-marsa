<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'whatsapp_logs',
            function (Blueprint $table) {
                $table->unique(
                    [
                        'registration_id',
                        'message_type',
                    ],
                    'wa_logs_registration_message_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'whatsapp_logs',
            function (Blueprint $table) {
                $table->dropUnique(
                    'wa_logs_registration_message_unique'
                );
            }
        );
    }
};