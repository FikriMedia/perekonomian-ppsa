<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ulasan extends Model
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';

    // status sengaja ada di $fillable untuk admin; form publik hanya meneruskan
    // nama_pengunjung, rating, komentar (lihat UlasanRequest).
    protected $fillable = ['nama_pengunjung', 'rating', 'komentar', 'status'];

    protected $attributes = [
        'status' => self::PENDING,
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
        ];
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::APPROVED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::PENDING);
    }

    protected function inisial(): Attribute
    {
        return Attribute::get(function () {
            return Str::of($this->nama_pengunjung)
                ->explode(' ')
                ->filter()
                ->take(2)
                ->map(fn ($kata) => Str::upper(Str::substr($kata, 0, 1)))
                ->implode('');
        });
    }
}
