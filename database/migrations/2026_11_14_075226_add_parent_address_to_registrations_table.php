<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('parent_hamlet', 100)->nullable();
            $table->string('parent_rt', 10)->nullable();
            $table->string('parent_rw', 10)->nullable();
            $table->string('parent_village', 100)->nullable();
            $table->string('parent_district', 100)->nullable();
            $table->string('parent_city', 100)->nullable();
            $table->string('parent_province', 100)->nullable();
            $table->string('parent_postal_code', 10)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn([
                'parent_hamlet',
                'parent_rt',
                'parent_rw',
                'parent_village',
                'parent_district',
                'parent_city',
                'parent_province',
                'parent_postal_code',
            ]);
        });
    }
};
