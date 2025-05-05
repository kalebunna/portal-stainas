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
        Schema::create('prodis', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('slug')->unique();
            $table->string('jenjang'); // S1, S2, D3, dsb
            $table->string('kode')->nullable();
            $table->text('deskripsi')->nullable();
            $table->text('visi')->nullable();
            $table->text('misi')->nullable();
            $table->string('akreditasi')->nullable();
            $table->string('gelar')->nullable();
            $table->string('icon')->nullable();
            $table->string('gambar')->nullable();
            $table->string('ketua_prodi')->nullable();
            $table->text('kompetensi')->nullable();
            $table->text('kurikulum')->nullable();
            $table->text('prospek_kerja')->nullable();
            $table->integer('durasi_tahun')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prodis');
    }
};
