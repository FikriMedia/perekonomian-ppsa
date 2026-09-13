<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Unit extends Model
{
    protected $fillable = ['nama_unit', 'deskripsi', 'parent_id', 'logo_path', 'logo_lebar', 'tampil_hero'];

    protected function casts(): array
    {
        // logo_lebar tidak di-cast: nilainya dibaca lewat accessor logoLebar() yang juga menangani logo statis.
        return [
            'tampil_hero' => 'boolean',
        ];
    }

    /**
     * Logo cadangan statis di public/images/logo, dicocokkan dari nama unit.
     * Dipakai selama admin belum mengunggah logo (kolom logo_path kosong).
     */
    private const LOGO = [
        // kata kunci => [path, logo melebar (rasio ~4:1)?]
        'toserba'  => ['images/logo/toserba.png', true],
        'torasera' => ['images/logo/torasera.png', true],
        'resto'    => ['images/logo/resto.png', false],
        'coffee'   => ['images/logo/coffee.png', false],
        'kopi'     => ['images/logo/coffee.png', false],
        'bakery'   => ['images/logo/bakery.png', false],
        'roti'     => ['images/logo/bakery.png', false],
        'airsa'    => ['images/logo/airsa.png', false],
    ];

    protected static function booted(): void
    {
        // Hapus lewat Eloquent (bukan hanya cascade DB) agar event Produk ikut jalan
        // dan file foto di storage ikut terhapus.
        static::deleting(function (Unit $unit) {
            $unit->children()->get()->each->delete();
            $unit->produks()->get()->each->delete();
            $unit->hapusLogo();
        });
    }

    public function hapusLogo(): void
    {
        if ($this->logo_path) {
            Storage::disk('public')->delete($this->logo_path);
        }
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Unit::class, 'parent_id')->orderBy('id');
    }

    public function produks(): HasMany
    {
        return $this->hasMany(Produk::class)->orderBy('id');
    }

    public function scopeInduk(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /** ID unit induk (dipakai untuk membuka tab yang benar setelah redirect). */
    protected function rootId(): Attribute
    {
        return Attribute::get(fn () => $this->parent_id ?? $this->id);
    }

    /** Logo unggahan admin, atau logo cadangan statis dari nama unit. */
    protected function logoUrl(): Attribute
    {
        return Attribute::get(function () {
            if ($this->logo_path) {
                return Storage::disk('public')->url($this->logo_path);
            }

            return ($logo = $this->cariLogo()) ? asset($logo[0]) : null;
        });
    }

    /** true untuk logo memanjang (mis. Toserba, Torasera) agar tidak dipaksa masuk kotak persegi. */
    protected function logoLebar(): Attribute
    {
        return Attribute::get(
            fn ($value) => $this->logo_path ? (bool) $value : (bool) ($this->cariLogo()[1] ?? false)
        );
    }

    private function cariLogo(): ?array
    {
        $nama = Str::lower((string) $this->nama_unit);

        foreach (self::LOGO as $kata => $logo) {
            if (Str::contains($nama, $kata)) {
                return $logo;
            }
        }

        return null;
    }
}
