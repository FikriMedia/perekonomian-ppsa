<?php

namespace App\Support;

/**
 * Tata letak logo unit di hero (desktop) untuk 0-12 logo.
 *
 * Koordinat memakai sistem SVG 1000x340 dengan logo pusat di (500,170). Setiap sisi punya
 * templat posisi untuk 0-6 logo; sisi kanan adalah cerminan sisi kiri. Logo di y=170 duduk
 * di garis utama, logo lain disambung dengan garis cabang miring + titik siku.
 */
class TataLetakHero
{
    public const MAKS = 12;

    /** Templat sisi kiri: [x, y, besar]. Urutan = urutan pengisian. */
    private const KIRI = [
        0 => [],
        1 => [[90, 170, true]],
        2 => [[250, 62, false], [265, 282, false]],
        3 => [[90, 170, true], [250, 62, false], [265, 282, false]],
        4 => [[90, 170, true], [250, 62, false], [265, 282, false], [300, 170, false]],
        5 => [[90, 170, true], [250, 62, false], [265, 282, false], [95, 45, false], [95, 295, false]],
        6 => [[90, 170, true], [250, 62, false], [265, 282, false], [95, 45, false], [95, 295, false], [300, 170, false]],
    ];

    /**
     * @return array{slot: list<array{x: float, y: float, besar: bool, d: float}>, utama: ?string, cabang: list<string>, titik: list<array{0:int,1:int}>, padat: bool}
     */
    public static function untuk(int $jumlah): array
    {
        $jumlah = max(0, min(self::MAKS, $jumlah));
        $kiri = self::KIRI[(int) ceil($jumlah / 2)];
        $kanan = self::KIRI[intdiv($jumlah, 2)];

        // Sisi kanan dicerminkan; logo pertama (posisi tengah) dipindah ke akhir agar urutan 6 logo
        // tetap: kiri-tengah, kiri-atas, kiri-bawah, kanan-atas, kanan-bawah, kanan-tengah.
        $kanan = array_map(fn ($p) => [1000 - $p[0], $p[1], $p[2]], $kanan);
        if (count($kanan) >= 3) {
            $kanan[] = array_shift($kanan);
        }

        $slot = [];
        $cabang = [];
        $titik = [];
        $ujungKiri = 500;
        $ujungKanan = 500;

        foreach ([[$kiri, 1], [$kanan, -1]] as [$sisi, $arah]) {
            foreach ($sisi as [$x, $y, $besar]) {
                $slot[] = ['x' => $x / 10, 'y' => round($y / 3.4, 2), 'besar' => $besar, 'd' => round(.15 + count($slot) * .05, 2)];

                if ($y === 170) {
                    $ujung = $x;
                } else {
                    $siku = $x + 50 * $arah;
                    $ujung = $x + 100 * $arah;   // titik cabang di garis utama
                    $cabang[] = "M{$ujung} 170 {$siku} {$y}H{$x}";
                    $titik[] = [$siku, $y];
                }

                $ujungKiri = min($ujungKiri, $ujung);
                $ujungKanan = max($ujungKanan, $ujung);
            }
        }

        return [
            'slot'   => $slot,
            'utama'  => $jumlah ? "M{$ujungKiri} 170H{$ujungKanan}" : null,
            'cabang' => $cabang,
            'titik'  => $titik,
            'padat'  => $jumlah > 6, // lebih dari 6 logo: ubin diperkecil
        ];
    }
}
