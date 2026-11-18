<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->unsignedSmallInteger('graduation_year')
                ->nullable();
        });

        Schema::table('ppdb_periods', function (Blueprint $table) {
            $table->boolean('show_graduation_score')
                ->default(true);

            $table->boolean('enable_achievement_relief')
                ->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('ppdb_periods', function (Blueprint $table) {
            $table->dropColumn([
                'show_graduation_score',
                'enable_achievement_relief',
            ]);
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('graduation_year');
        });
    }
};
