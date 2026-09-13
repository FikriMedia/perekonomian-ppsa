<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $table->string('nama_produk', 150);
            $table->text('deskripsi')->nullable();
            $table->unsignedBigInteger('harga')->default(0); // dalam Rupiah
            $table->string('foto_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produks');
    }
};
