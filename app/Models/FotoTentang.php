<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FotoTentang extends Model
{
    public const MAKS = 6;

    protected $fillable = ['foto_path'];

    protected static function booted(): void
    {
        static::deleting(function (FotoTentang $foto) {
            Storage::disk('public')->delete($foto->foto_path);
        });
    }

    protected function fotoUrl(): Attribute
    {
        return Attribute::get(fn () => Storage::disk('public')->url($this->foto_path));
    }
}
