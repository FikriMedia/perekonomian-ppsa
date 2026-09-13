<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Berita extends Model
{
    protected $fillable = ['judul', 'ringkasan', 'isi', 'foto_path', 'terbit_pada'];

    protected function casts(): array
    {
        return [
            'terbit_pada' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Berita $berita) {
            $berita->slug ??= static::slugUnik($berita->judul);
        });

        static::deleting(function (Berita $berita) {
            if ($berita->foto_path) {
                Storage::disk('public')->delete($berita->foto_path);
            }
        });
    }

    public function scopeTerbaru(Builder $query): Builder
    {
        return $query->orderByDesc('terbit_pada')->orderByDesc('id');
    }

    protected function fotoUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->foto_path ? Storage::disk('public')->url($this->foto_path) : null
        );
    }

    /** Ringkasan dari admin, atau potongan awal isi berita. */
    protected function cuplikan(): Attribute
    {
        return Attribute::get(
            fn () => $this->ringkasan ?: Str::limit(preg_replace('/\s+/', ' ', (string) $this->isi), 160)
        );
    }

    /** Isi berita dipecah per paragraf (dipisah baris kosong) agar bisa dirender tanpa HTML dari admin. */
    protected function paragraf(): Attribute
    {
        return Attribute::get(
            fn (): Collection => collect(preg_split('/(\r\n|\r|\n)\s*(\r\n|\r|\n)/', trim((string) $this->isi)))
                ->map(fn ($p) => trim($p))
                ->filter()
                ->values()
        );
    }

    /** Perkiraan waktu baca, ~200 kata per menit. */
    protected function menitBaca(): Attribute
    {
        return Attribute::get(fn () => max(1, (int) ceil(str_word_count(strip_tags((string) $this->isi)) / 200)));
    }

    private static function slugUnik(string $judul): string
    {
        $dasar = Str::slug($judul) ?: 'berita';
        $slug = $dasar;

        for ($i = 2; static::where('slug', $slug)->exists(); $i++) {
            $slug = "{$dasar}-{$i}";
        }

        return $slug;
    }
}
