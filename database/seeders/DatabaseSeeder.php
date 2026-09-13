<?php

namespace Database\Seeders;

use App\Models\Cabang;
use App\Models\Pengaturan;
use App\Models\Ulasan;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Akun admin. GANTI password setelah login pertama / set ADMIN_PASSWORD di .env.
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@abdussalam.test')],
            ['name' => 'Admin Perkom', 'password' => Hash::make(env('ADMIN_PASSWORD', 'password'))]
        );

        $this->pengaturan();
        $this->unitDanProduk();

        // ---- Data contoh di bawah ini hanya untuk pengembangan. Hapus/ubah sebelum rilis. ----
        // Mitra tidak di-seed: Airsa, Toserba & Torasera adalah unit usaha sendiri. Tambah mitra lewat halaman depan.
        $this->cabangContoh();
        $this->ulasanContoh();
        $this->call(BeritaContohSeeder::class);
    }

    private function pengaturan(): void
    {
        $nilai = [
            'hero_judul'     => 'Satu pesantren, banyak usaha',
            'hero_deskripsi' => 'Restoran, Salam Coffee, Salam Bakery, air mineral Airsa, Toserba, dan Torasera adalah unit usaha Pondok Pesantren Abdussalam, Kubu Raya. Setiap belanja Anda ikut menopang kemandirian pesantren.',
            'whatsapp'       => '081234567890',
            'visi'           => 'Menjadi pusat ekonomi pesantren yang mandiri, amanah, dan memberi manfaat bagi santri serta masyarakat sekitar.',
            'misi'           => "Mengelola unit usaha secara profesional dan sesuai syariah.\nMembuka ruang belajar wirausaha bagi santri.\nMenyediakan produk halal berkualitas dengan harga terjangkau.\nBermitra dengan pelaku usaha lokal Kalimantan Barat.",
        ];

        foreach ($nilai as $key => $value) {
            Pengaturan::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    private function unitDanProduk(): void
    {
        $resto = Unit::firstOrCreate(['nama_unit' => 'Restoran Abdussalam'], [
            'deskripsi' => 'Masakan rumahan khas Kalimantan Barat untuk makan bersama keluarga dan rombongan.',
        ]);
        $resto->produks()->firstOrCreate(['nama_produk' => 'Nasi Ayam Bakar'], ['harga' => 25000, 'deskripsi' => 'Ayam bakar bumbu kecap, nasi, lalapan, dan sambal.']);
        $resto->produks()->firstOrCreate(['nama_produk' => 'Ikan Patin Asam Pedas'], ['harga' => 30000, 'deskripsi' => 'Patin segar berkuah asam pedas.']);

        $prasmanan = Unit::firstOrCreate(['nama_unit' => 'Paket Prasmanan', 'parent_id' => $resto->id], [
            'deskripsi' => 'Pesanan untuk acara, minimal 20 porsi.',
        ]);
        $prasmanan->produks()->firstOrCreate(['nama_produk' => 'Paket Walimah'], ['harga' => 35000, 'deskripsi' => 'Per porsi: nasi, 2 lauk, sayur, buah, air mineral.']);

        $coffee = Unit::firstOrCreate(['nama_unit' => 'Salam Coffee'], [
            'deskripsi' => 'Kopi dan minuman dingin untuk teman ngobrol dan belajar.',
        ]);
        $coffee->produks()->firstOrCreate(['nama_produk' => 'Kopi Susu Salam'], ['harga' => 15000, 'deskripsi' => 'Espresso, susu segar, dan gula aren.']);
        $coffee->produks()->firstOrCreate(['nama_produk' => 'Americano'], ['harga' => 12000]);

        $bakery = Unit::firstOrCreate(['nama_unit' => 'Salam Bakery'], [
            'deskripsi' => 'Roti dan kue yang dipanggang setiap pagi.',
        ]);
        $bakery->produks()->firstOrCreate(['nama_produk' => 'Roti Sobek Cokelat'], ['harga' => 18000]);
        $bakery->produks()->firstOrCreate(['nama_produk' => 'Bolu Pandan'], ['harga' => 45000, 'deskripsi' => 'Satu loyang, cocok untuk oleh-oleh.']);

        $airsa = Unit::firstOrCreate(['nama_unit' => 'Airsa'], [
            'deskripsi' => 'Air mineral Abdussalam. Segar dan menyehatkan.',
        ]);
        $airsa->produks()->firstOrCreate(['nama_produk' => 'Airsa Botol 600 ml'], ['harga' => 3000]);
        $airsa->produks()->firstOrCreate(['nama_produk' => 'Airsa Galon 19 L'], ['harga' => 20000]);

        Unit::firstOrCreate(['nama_unit' => 'Toserba Abdussalam'], [
            'deskripsi' => 'Toko serba ada untuk kebutuhan harian santri dan warga sekitar.',
        ]);

        Unit::firstOrCreate(['nama_unit' => 'Torasera'], [
            'deskripsi' => 'Toko Rakyat Serba Ada.',
        ]);
    }

    private function cabangContoh(): void
    {
        $query = rawurlencode('Pondok Pesantren Abdussalam Kubu Raya');

        Cabang::firstOrCreate(['nama_cabang' => 'Toserba Abdussalam Pusat'], [
            'alamat'      => 'Kompleks Pondok Pesantren Abdussalam, Kubu Raya, Kalimantan Barat',
            'link_maps'   => "https://www.google.com/maps/search/?api=1&query={$query}",
            'no_whatsapp' => '081234567890',
            'media_type'  => Cabang::MEDIA_LINK,
            'media_path'  => "https://www.google.com/maps?q={$query}&output=embed",
        ]);
    }

    private function ulasanContoh(): void
    {
        if (Ulasan::exists()) {
            return;
        }

        $contoh = [
            ['Pengunjung Contoh A', 5, 'Tempatnya bersih dan pelayanannya ramah. Kopi susunya enak.', Ulasan::APPROVED],
            ['Pengunjung Contoh B', 4, 'Roti sobeknya lembut, pas untuk sarapan sebelum berangkat kerja.', Ulasan::APPROVED],
            ['Pengunjung Contoh C', 5, 'Pesan paket prasmanan untuk acara keluarga, porsinya pas dan tepat waktu.', Ulasan::APPROVED],
            ['Pengunjung Contoh D', 4, 'Toserbanya lengkap, harga bersahabat.', Ulasan::PENDING],
        ];

        foreach ($contoh as [$nama, $rating, $komentar, $status]) {
            Ulasan::create(compact('rating', 'komentar', 'status') + ['nama_pengunjung' => $nama]);
        }
    }
}
