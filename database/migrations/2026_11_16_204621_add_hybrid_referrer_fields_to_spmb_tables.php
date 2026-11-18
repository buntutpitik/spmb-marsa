<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('referral_sources', function (Blueprint $table) {
            $table->string('code', 50)->nullable()->unique();
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('referral_source_id')->nullable()->constrained('referral_sources')->restrictOnDelete();
            $table->string('referrer_class', 100)->nullable();
            $table->foreignId('referrer_registration_id')->nullable()->constrained('registrations')->restrictOnDelete();
            $table->text('referrer_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referrer_registration_id');
            $table->dropColumn('referrer_notes');
            $table->dropColumn('referrer_class');
            $table->dropConstrainedForeignId('referral_source_id');
        });

        Schema::table('referral_sources', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
