<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('parent_id');
            // true untuk logo memanjang (rasio >= 1.8), dideteksi saat unggah.
            $table->boolean('logo_lebar')->default(false)->after('logo_path');
            $table->boolean('tampil_hero')->default(true)->after('logo_lebar');
        });

        // Urutan logo hero dulu disimpan sebagai kata kunci (mis. "resto"); ubah ke ID unit agar susunan tidak berubah.
        $lama = json_decode((string) DB::table('pengaturans')->where('key', 'hero_urutan_logo')->value('value'), true);
        if (! is_array($lama) || ! $lama || is_int($lama[0] ?? null)) {
            return;
        }

        $kataKunci = ['resto' => ['resto'], 'coffee' => ['coffee', 'kopi'], 'bakery' => ['bakery', 'roti'],
            'toserba' => ['toserba'], 'torasera' => ['torasera'], 'airsa' => ['airsa']];
        $units = DB::table('units')->orderBy('id')->get(['id', 'nama_unit']);

        $ids = collect($lama)
            ->map(fn ($kunci) => $units->first(fn ($u) => Str::contains(Str::lower($u->nama_unit), $kataKunci[$kunci] ?? ['@@']))?->id)
            ->filter()
            ->unique()
            ->values();

        DB::table('pengaturans')->where('key', 'hero_urutan_logo')
            ->update(['value' => $ids->isEmpty() ? null : $ids->toJson()]);
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'logo_lebar', 'tampil_hero']);
        });
    }
};
