<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Produk extends Model
{
    protected $fillable = ['unit_id', 'nama_produk', 'deskripsi', 'harga', 'foto_path'];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Produk $produk) {
            if ($produk->foto_path) {
                Storage::disk('public')->delete($produk->foto_path);
            }
        });
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    protected function fotoUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->foto_path ? Storage::disk('public')->url($this->foto_path) : null
        );
    }

    protected function hargaRupiah(): Attribute
    {
        return Attribute::get(fn () => 'Rp' . number_format($this->harga, 0, ',', '.'));
    }
}
