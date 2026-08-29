<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_page_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained('schools')
                ->cascadeOnDelete();

            /*
             * Hero
             */
            $table->string('hero_title', 200)->nullable();
            $table->string('hero_subtitle', 255)->nullable();
            $table->text('hero_description')->nullable();

            /*
             * Pengumuman
             */
            $table->string('announcement_title', 200)->nullable();
            $table->text('announcement_body')->nullable();
            $table->boolean('show_announcement')->default(true);

            /*
             * Persyaratan
             *
             * Disimpan sebagai teks agar admin dapat menulis
             * beberapa baris/poin tanpa struktur yang terlalu rumit.
             */
            $table->text('requirements')->nullable();
            $table->boolean('show_requirements')->default(true);

            /*
             * Cara Mendaftar
             */
            $table->text('registration_steps')->nullable();
            $table->boolean('show_registration_steps')->default(true);

            /*
             * Informasi Daftar Ulang
             */
            $table->text('reenrollment_information')->nullable();
            $table->boolean('show_reenrollment_information')->default(true);

            /*
             * Kontak mengambil data dari tabel schools.
             * Field ini hanya menentukan apakah section ditampilkan.
             */
            $table->boolean('show_contact')->default(true);

            $table->timestamps();

            /*
             * Satu konfigurasi halaman publik untuk satu sekolah.
             */
            $table->unique('school_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_page_settings');
    }
};