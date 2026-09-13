<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Cabang extends Model
{
    public const MEDIA_FILE = 'file';
    public const MEDIA_LINK = 'link';

    protected $fillable = [
        'nama_cabang',
        'alamat',
        'link_maps',
        'no_whatsapp',
        'media_type',
        'media_path',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Cabang $cabang) {
            $cabang->hapusFileMedia();
        });
    }

    public function hapusFileMedia(): void
    {
        if ($this->media_type === self::MEDIA_FILE && $this->media_path) {
            Storage::disk('public')->delete($this->media_path);
        }
    }

    /** Simpan nomor WA dalam format internasional: 0812... / +62812... => 62812... */
    protected function noWhatsapp(): Attribute
    {
        return Attribute::set(function (?string $value) {
            $angka = preg_replace('/\D/', '', (string) $value);

            return Str::startsWith($angka, '0') ? '62' . substr($angka, 1) : $angka;
        });
    }

    protected function whatsappUrl(): Attribute
    {
        return Attribute::get(function () {
            $pesan = "Assalamu'alaikum, saya ingin bertanya tentang {$this->nama_cabang}.";

            return 'https://wa.me/' . $this->no_whatsapp . '?text=' . rawurlencode($pesan);
        });
    }

    /**
     * URL foto cabang. Cabang kini hanya memakai foto; data lama berupa video,
     * link YouTube, atau embed peta dianggap belum punya foto.
     */
    protected function fotoUrl(): Attribute
    {
        return Attribute::get(
            fn () => $this->media_type === self::MEDIA_FILE
                && $this->media_path
                && Str::endsWith(Str::lower($this->media_path), ['.jpg', '.jpeg', '.png', '.webp'])
                    ? Storage::disk('public')->url($this->media_path)
                    : null
        );
    }

    /**
     * Link untuk tombol "Buka peta". Cabang lama yang menyimpan embed Google Maps
     * sebagai media tetap punya tombol peta walau link_maps kosong.
     */
    protected function petaUrl(): Attribute
    {
        return Attribute::get(function () {
            if ($this->link_maps) {
                return $this->link_maps;
            }

            $media = (string) $this->media_path;

            return $this->media_type === self::MEDIA_LINK && Str::contains($media, ['google.com/maps', 'maps.app.goo.gl', 'goo.gl/maps'])
                ? $media
                : null;
        });
    }

    /** ID video dari link YouTube (dipakai background hero). */
    public static function youtubeId(string $url): ?string
    {
        return preg_match('~(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([\w-]{11})~', $url, $m) ? $m[1] : null;
    }
}
