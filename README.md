# Landing Page & In-Place CMS: Perekonomian PP Abdussalam

Landing page untuk unit usaha Pondok Pesantren Abdussalam. Admin mengedit konten langsung di halaman depan lewat modal Alpine.js. Tombol edit hanya muncul setelah login (`@auth`).

Folder ini **bukan proyek Laravel lengkap**. Isinya file yang ditambahkan atau diganti di atas instalasi Laravel baru.

## Pemasangan

```bash
# 1. Buat proyek Laravel baru (lihat catatan versi di bawah)
composer create-project laravel/laravel perkom-abdussalam
cd perkom-abdussalam

# 2. Salin seluruh isi folder abdussalam-cms ke folder proyek (timpa jika ditanya)

# 3. Atur .env
#    APP_LOCALE=id
#    APP_URL=http://127.0.0.1:8000
#    ADMIN_EMAIL=admin@abdussalam.test
#    ADMIN_PASSWORD=ganti-dengan-sandi-kuat
#    (DB_* sesuai MySQL Anda; bawaan Laravel memakai SQLite)

# 4. Database, data awal, dan symlink storage
php artisan migrate --seed
php artisan storage:link

# 5. Jalankan
php artisan serve
```

Buka `http://127.0.0.1:8000`, lalu masuk lewat tautan **Masuk admin** di footer (`/login`).

Pesan validasi berbahasa Indonesia (`lang/id/validation.php`) aktif selama `APP_LOCALE=id`.

Login admin dibatasi 5 percobaan per menit per email + IP. Jika melebihi batas, pesannya tampil di form login.

## Catatan versi Laravel

Laravel 11 sudah melewati masa dukungan keamanannya (berakhir 12 Maret 2026). Karena itu Composer versi baru **menolak memasang** Laravel 11 secara default. Kode ini sudah diuji di Laravel 11 dan 12 tanpa perubahan. Untuk produksi, gunakan Laravel 12 atau yang lebih baru.

## Struktur

| Bagian | File |
|---|---|
| Migration (6 tabel) | `database/migrations/2026_09_13_00000{1-6}_*.php` |
| Model + relasi | `app/Models/{Pengaturan,Unit,Produk,Cabang,Mitra,Ulasan}.php` |
| Controller publik | `app/Http/Controllers/LandingPageController.php` (`index`, `storeUlasan`) |
| Controller admin | `app/Http/Controllers/Admin/*Controller.php` (store/update/destroy per tabel) |
| Validasi | `app/Http/Requests/*Request.php` |
| Rate limit ulasan (3/jam per IP) | `app/Providers/AppServiceProvider.php` |
| Routes | `routes/web.php` |
| Halaman | `resources/views/welcome.blade.php` |
| Komponen | `resources/views/components/{modal,modal-aksi,field-error,produk-grid,ikon}.blade.php` |
| Login admin | `app/Http/Controllers/AuthController.php`, `resources/views/auth/login.blade.php` |
| Logo (sudah dikecilkan untuk web) | `public/images/logo/*.png` |
| Terjemahan validasi | `lang/id/validation.php` |
| Tes (10 tes) | `tests/Feature/InPlaceCmsTest.php` (`php artisan test`) |

## Cara kerja in-place editing

- Semua modal dikendalikan satu store Alpine: `$store.modal.open('produk', {...data})`.
- Form tambah dan edit memakai modal yang sama. Jika `data.id` ada, form dikirim ke route `update` dengan `_method=PUT`. Jika tidak, ke `store`.
- Setiap form berisi `@csrf`. Hapus memakai modal konfirmasi dengan `@method('DELETE')`.
- Jika validasi gagal, hidden input `_modal` dan `_id` membuat modal yang sama terbuka lagi, lengkap dengan isian lama dan pesan error.
- Menghapus unit ikut menghapus sub-unit, produk, dan file fotonya. File lama juga dihapus saat foto atau media diganti.

## Yang perlu disesuaikan sebelum rilis

- Seeder membuat 6 unit usaha: Restoran Abdussalam, Salam Coffee, Salam Bakery, Airsa, Toserba Abdussalam, Torasera. Produk, harga, nomor WhatsApp `081234567890`, alamat cabang, dan ulasan "Pengunjung Contoh" hanyalah placeholder.
- Mitra tidak di-seed. Section Mitra baru tampil untuk pengunjung setelah admin menambahkan minimal satu mitra.
- Logo unit dicocokkan dari nama unit (`Unit::LOGO`), karena tabel `units` tidak punya kolom logo. Nama unit harus tetap mengandung kata kunci `resto`, `coffee`/`kopi`, `bakery`/`roti`, `airsa`, `toserba`, atau `torasera`.
- Tailwind dimuat dari Play CDN sesuai permintaan. Untuk produksi, sebaiknya dikompilasi lewat Vite agar lebih ringan.
- Batas unggah video cabang 20 MB. Pastikan `upload_max_filesize` dan `post_max_size` di `php.ini` minimal 25M.
