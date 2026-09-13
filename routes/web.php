<?php

use App\Http\Controllers\Admin\BeritaController as AdminBeritaController;
use App\Http\Controllers\Admin\CabangController;
use App\Http\Controllers\Admin\LatarHeroController;
use App\Http\Controllers\Admin\LogoHeroController;
use App\Http\Controllers\Admin\MitraController;
use App\Http\Controllers\Admin\PengaturanController;
use App\Http\Controllers\Admin\ProdukController;
use App\Http\Controllers\Admin\TentangController;
use App\Http\Controllers\Admin\UlasanController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BeritaController;
use App\Http\Controllers\LandingPageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Publik
|--------------------------------------------------------------------------
*/
Route::get('/', [LandingPageController::class, 'index'])->name('home');

Route::get('/berita', [BeritaController::class, 'index'])->name('berita.index');
Route::get('/berita/{berita:slug}', [BeritaController::class, 'show'])->name('berita.show');

// Maks. 3 ulasan per jam per IP. Limiter 'ulasan' didefinisikan di AppServiceProvider
// agar pengunjung dikembalikan ke form dengan pesan, bukan halaman error 429.
Route::post('/ulasan', [LandingPageController::class, 'storeUlasan'])
    ->middleware('throttle:ulasan')
    ->name('ulasan.store');

/*
|--------------------------------------------------------------------------
| Login admin
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    // Pembatasan 5 percobaan/menit ada di AuthController agar tampil sebagai pesan di form.
    Route::post('/login', [AuthController::class, 'store']);
});

Route::post('/logout', [AuthController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Admin: In-place CMS (dipanggil dari modal di halaman depan)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('admin')->name('admin.')->group(function () {
    Route::put('/pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
    Route::put('/latar-hero', [LatarHeroController::class, 'update'])->name('latar-hero.update');
    Route::put('/logo-hero', [LogoHeroController::class, 'update'])->name('logo-hero.update');

    Route::post('/unit', [UnitController::class, 'store'])->name('unit.store');
    Route::put('/unit/{unit}', [UnitController::class, 'update'])->name('unit.update');
    Route::delete('/unit/{unit}', [UnitController::class, 'destroy'])->name('unit.destroy');

    Route::post('/produk', [ProdukController::class, 'store'])->name('produk.store');
    Route::put('/produk/{produk}', [ProdukController::class, 'update'])->name('produk.update');
    Route::delete('/produk/{produk}', [ProdukController::class, 'destroy'])->name('produk.destroy');

    Route::post('/cabang', [CabangController::class, 'store'])->name('cabang.store');
    Route::put('/cabang/{cabang}', [CabangController::class, 'update'])->name('cabang.update');
    Route::delete('/cabang/{cabang}', [CabangController::class, 'destroy'])->name('cabang.destroy');

    Route::post('/mitra', [MitraController::class, 'store'])->name('mitra.store');
    Route::put('/mitra/{mitra}', [MitraController::class, 'update'])->name('mitra.update');
    Route::delete('/mitra/{mitra}', [MitraController::class, 'destroy'])->name('mitra.destroy');

    Route::post('/berita', [AdminBeritaController::class, 'store'])->name('berita.store');
    Route::put('/berita/{berita}', [AdminBeritaController::class, 'update'])->name('berita.update');
    Route::delete('/berita/{berita}', [AdminBeritaController::class, 'destroy'])->name('berita.destroy');

    Route::put('/tentang', [TentangController::class, 'update'])->name('tentang.update');
    Route::post('/tentang/foto', [TentangController::class, 'storeFoto'])->name('tentang.foto.store');
    Route::delete('/tentang/foto/{foto}', [TentangController::class, 'destroyFoto'])->name('tentang.foto.destroy');

    Route::patch('/ulasan/{ulasan}/setujui', [UlasanController::class, 'approve'])->name('ulasan.approve');
    Route::delete('/ulasan/{ulasan}', [UlasanController::class, 'destroy'])->name('ulasan.destroy');
});
