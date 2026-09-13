<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('beritas', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 200);
            // Dibuat sekali saat berita dibuat; tidak ikut berubah saat judul diedit agar link yang sudah dibagikan tetap hidup.
            $table->string('slug', 220)->unique();
            $table->string('ringkasan', 300)->nullable();
            $table->longText('isi');
            $table->string('foto_path')->nullable();
            $table->date('terbit_pada')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beritas');
    }
};
