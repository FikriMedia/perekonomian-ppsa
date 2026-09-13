<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Pengaturan extends Model
{
    /** Kunci yang boleh diubah dari halaman depan. */
    public const KUNCI = [
        'hero_judul', 'hero_deskripsi', 'whatsapp', 'visi', 'misi',
        'tentang_judul', 'tentang_isi', 'tentang_statistik', // tentang_statistik: JSON [{angka, label}]
        'hero_background', // JSON, lihat latarHero()
        'hero_urutan_logo', // JSON: urutan ID unit pada posisi logo hero
        'cabang_judul', 'cabang_deskripsi',
    ];

    /**
     * Urutan ID unit untuk logo hero: ID tersimpan yang masih tersedia lebih dulu,
     * lalu unit tersedia lainnya (mis. unit yang baru diberi logo) sesuai urutan bawaan.
     *
     * @param  list<int>  $tersedia  ID unit yang boleh tampil, dalam urutan bawaan
     * @return list<int>
     */
    public static function urutanLogoHero(?string $json, array $tersedia): array
    {
        $tersimpan = array_map('intval', array_filter((array) json_decode((string) $json, true), 'is_numeric'));

        return array_values(array_unique([
            ...array_intersect($tersimpan, $tersedia),
            ...array_diff($tersedia, $tersimpan),
        ]));
    }

    /** Nilai bawaan background hero. */
    public const LATAR_HERO = [
        'tipe'   => 'bawaan',   // bawaan | gradasi | foto | video
        'warna1' => '#2F8A34',
        'warna2' => '#14231A',
        'arah'   => 135,        // derajat gradasi
        'sumber' => 'file',     // file | link (untuk foto & video)
        'path'   => null,       // path file di disk public
        'link'   => null,       // URL gambar / video / YouTube
        'gelap'  => 35,         // lapisan gelap di atas foto/video, 0-80 (%)
        'teks'   => 'terang',   // gelap | terang
    ];

    /**
     * Background hero siap pakai di view: nilai tersimpan + bawaan, ditambah
     * `url` (file atau link) dan `youtube` (ID video bila link YouTube).
     */
    public static function latarHero(?string $json): array
    {
        $latar = array_merge(self::LATAR_HERO, array_intersect_key(json_decode((string) $json, true) ?: [], self::LATAR_HERO));

        $latar['url'] = $latar['sumber'] === 'file'
            ? ($latar['path'] ? Storage::disk('public')->url($latar['path']) : null)
            : $latar['link'];
        $latar['youtube'] = $latar['tipe'] === 'video' && $latar['sumber'] === 'link'
            ? Cabang::youtubeId((string) $latar['link'])
            : null;

        // Foto/video tanpa sumber yang valid dianggap bawaan agar hero tidak kosong.
        if (in_array($latar['tipe'], ['foto', 'video'], true) && ! $latar['url']) {
            $latar['tipe'] = 'bawaan';
        }

        return $latar;
    }

    protected $fillable = ['key', 'value'];

    /**
     * Ambil beberapa pengaturan sekaligus sebagai koleksi key => value.
     */
    public static function ambil(array $keys = self::KUNCI): Collection
    {
        return static::whereIn('key', $keys)->pluck('value', 'key');
    }

    public static function simpan(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /** Link wa.me dari nomor 08xx / +628xx / 628xx. Null jika nomor kosong. */
    public static function whatsappUrl(?string $nomor, string $pesan = ''): ?string
    {
        $angka = preg_replace('/\D/', '', (string) $nomor);

        if ($angka === '') {
            return null;
        }

        if (str_starts_with($angka, '0')) {
            $angka = '62' . substr($angka, 1);
        }

        return 'https://wa.me/' . $angka . ($pesan !== '' ? '?text=' . rawurlencode($pesan) : '');
    }
}
