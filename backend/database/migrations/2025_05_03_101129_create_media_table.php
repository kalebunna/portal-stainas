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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('file');
            $table->string('tipe'); // image, document, video
            $table->string('mime')->nullable();
            $table->bigInteger('ukuran')->nullable(); // dalam bytes
            $table->morphs('mediable'); // untuk relasi polymorphic

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
