<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cabangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_cabang', 150);
            $table->text('alamat');
            $table->text('link_maps')->nullable();
            $table->string('no_whatsapp', 20);
            $table->enum('media_type', ['file', 'link'])->default('file');
            // 'file' => path di disk public, 'link' => URL (embed maps / YouTube). URL embed bisa panjang, jadi text.
            $table->text('media_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cabangs');
    }
};
