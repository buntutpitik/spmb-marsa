<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique(
                'registrations_registration_number_unique'
            );

            $table->unique(
                ['period_id', 'registration_number'],
                'registrations_period_number_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropUnique(
                'registrations_period_number_unique'
            );

            $table->unique(
                'registration_number',
                'registrations_registration_number_unique'
            );
        });
    }
};