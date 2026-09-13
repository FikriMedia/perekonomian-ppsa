<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('nama_unit', 100);
            $table->text('deskripsi')->nullable();
            // Self-referencing: sub-unit menunjuk ke unit induknya.
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('units')
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
