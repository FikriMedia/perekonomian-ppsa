<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Foto kegiatan yang melayang di sekitar teks section "Tentang kami" (maks. 6, dibatasi di request).
        Schema::create('foto_tentangs', function (Blueprint $table) {
            $table->id();
            $table->string('foto_path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('foto_tentangs');
    }
};
