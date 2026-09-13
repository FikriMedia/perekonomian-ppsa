<?php

namespace Database\Seeders;

use App\Models\Berita;
use Illuminate\Database\Seeder;

/**
 * Berita contoh untuk melihat tampilan. Hapus lewat "Kelola berita" di beranda sebelum rilis.
 * Jalankan terpisah: php artisan db:seed --class=BeritaContohSeeder
 */
class BeritaContohSeeder extends Seeder
{
    public function run(): void
    {
        if (Berita::exists()) {
            return;
        }

        $contoh = [
            [
                'judul'       => 'Contoh berita: Salam Coffee buka ruang belajar santri setiap sore',
                'ringkasan'   => 'Setiap sore, sudut Salam Coffee disiapkan untuk santri yang ingin belajar sambil menikmati kopi.',
                'terbit_pada' => now()->subDays(2),
                'isi'         => "Ini adalah contoh isi berita untuk melihat tampilan halaman. Ganti atau hapus lewat tombol Kelola berita di beranda setelah masuk sebagai admin.\n\nParagraf kedua dipisahkan dengan satu baris kosong. Tulis kabar kegiatan, peluncuran produk baru, atau pengumuman untuk pelanggan di sini.\n\nParagraf ketiga bisa berisi ajakan, misalnya mengunjungi cabang terdekat atau menghubungi admin lewat WhatsApp.",
            ],
            [
                'judul'       => 'Contoh berita: Salam Bakery terima pesanan kue untuk acara',
                'ringkasan'   => null,
                'terbit_pada' => now()->subDays(9),
                'isi'         => "Contoh berita tanpa ringkasan: kartu otomatis mengambil kalimat awal dari isi berita.\n\nPesanan bisa disesuaikan untuk walimah, syukuran, maupun kegiatan pesantren.",
            ],
            [
                'judul'       => 'Contoh berita: Santri belajar mengelola Toserba Abdussalam',
                'ringkasan'   => 'Santri kelas akhir praktik langsung mengatur stok, kasir, dan pelayanan pelanggan.',
                'terbit_pada' => now()->subDays(20),
                'isi'         => "Contoh isi berita tentang kegiatan belajar wirausaha santri di unit usaha pesantren.\n\nGanti dengan kabar asli beserta foto kegiatannya.",
            ],
        ];

        foreach ($contoh as $data) {
            Berita::create($data);
        }
    }
}
