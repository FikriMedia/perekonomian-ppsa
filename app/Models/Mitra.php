<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Mitra extends Model
{
    protected $fillable = ['nama_mitra', 'logo_path'];

    protected static function booted(): void
    {
        static::deleting(function (Mitra $mitra) {
            if ($mitra->logo_path) {
                Storage::disk('public')->delete($mitra->logo_path);
            }
        });
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null
        );
    }
}
