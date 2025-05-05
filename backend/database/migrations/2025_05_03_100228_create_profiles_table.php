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
        Schema::create('profiles', function (Blueprint $table) {
            $table->id();
            $table->string('nama_kampus');
            $table->string('singkatan')->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('logo')->nullable();
            $table->string('alamat')->nullable();
            $table->string('telepon')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->text('visi')->nullable();
            $table->text('misi')->nullable();
            $table->string('akreditasi')->nullable();
            $table->text('sejarah')->nullable();
            $table->string('maps_embed')->nullable();
            $table->string('rektor_name')->nullable();
            $table->string('rektor_photo')->nullable();
            $table->text('sambutan_rektor')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
