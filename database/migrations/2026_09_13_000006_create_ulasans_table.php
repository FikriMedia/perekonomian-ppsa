<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ulasans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pengunjung', 100);
            $table->unsignedTinyInteger('rating'); // 1-5, divalidasi di UlasanRequest
            $table->text('komentar');
            $table->enum('status', ['pending', 'approved'])->default('pending')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ulasans');
    }
};
