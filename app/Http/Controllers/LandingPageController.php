<?php

namespace App\Http\Controllers;

use App\Http\Requests\UlasanRequest;
use App\Models\Berita;
use App\Models\Cabang;
use App\Models\FotoTentang;
use App\Models\Mitra;
use App\Models\Pengaturan;
use App\Models\Produk;
use App\Models\Ulasan;
use App\Models\Unit;
use App\Support\TataLetakHero;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function index(): View
    {
        $pengaturan = Pengaturan::ambil();

        // Unit induk (tab) beserta produknya, sub-unit, dan produk sub-unit.
        $units = Unit::induk()
            ->with(['produks', 'children.produks'])
            ->orderBy('id')
            ->get();

        // Logo hero: unit (induk lebih dulu) yang punya logo & dicentang "tampilkan di hero", maks. 12.
        $kandidatHero = Unit::where('tampil_hero', true)
            ->orderByRaw('parent_id IS NOT NULL')
            ->orderBy('id')
            ->get()
            ->filter(fn (Unit $unit) => $unit->logo_url)
            ->keyBy('id');
        $unitHero = collect(Pengaturan::urutanLogoHero($pengaturan['hero_urutan_logo'] ?? null, $kandidatHero->keys()->all()))
            ->take(TataLetakHero::MAKS)
            ->map(fn ($id) => $kandidatHero[$id])
            ->values();
        $tataHero = TataLetakHero::untuk($unitHero->count());

        $cabangs = Cabang::orderBy('nama_cabang')->get();
        $mitras = Mitra::orderBy('nama_mitra')->get();

        $beritas = Berita::terbaru()->take(3)->get();
        $fotoTentang = FotoTentang::orderBy('id')->get();

        // Angka "Tentang kami": pakai isian admin, atau hitung otomatis dari data yang ada.
        $statistikTentang = collect(json_decode($pengaturan['tentang_statistik'] ?? '', true) ?: []);
        if ($statistikTentang->isEmpty()) {
            $statistikTentang = collect([
                ['angka' => (string) $units->count(), 'label' => 'Unit usaha'],
                ['angka' => (string) $cabangs->count(), 'label' => 'Cabang toserba'],
                ['angka' => (string) Produk::count(), 'label' => 'Produk & menu'],
            ]);
        }

        $ulasans = Ulasan::approved()->latest()->take(30)->get();
        $statistikUlasan = Ulasan::approved()
            ->selectRaw('COUNT(*) as jumlah, AVG(rating) as rata_rata')
            ->first();

        // Data tambahan hanya untuk admin (dropdown & moderasi ulasan).
        $isAdmin = auth()->check();
        $pilihanUnit = $isAdmin ? Unit::orderBy('parent_id')->orderBy('nama_unit')->get(['id', 'nama_unit', 'parent_id']) : collect();
        $ulasanAdmin = $isAdmin
            ? Ulasan::orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")->latest()->take(100)->get()
            : collect();
        $beritaAdmin = $isAdmin ? Berita::terbaru()->get() : collect();

        return view('welcome', compact(
            'pengaturan',
            'units',
            'unitHero',
            'tataHero',
            'cabangs',
            'mitras',
            'beritas',
            'beritaAdmin',
            'fotoTentang',
            'statistikTentang',
            'ulasans',
            'statistikUlasan',
            'pilihanUnit',
            'ulasanAdmin',
        ));
    }

    /** Form ulasan publik. Dibatasi 3 kiriman/jam per IP lewat RateLimiter 'ulasan'. */
    public function storeUlasan(UlasanRequest $request): RedirectResponse
    {
        Ulasan::create([
            ...$request->validated(),
            'status' => Ulasan::PENDING,
        ]);

        return back()
            ->withFragment('ulasan')
            ->with('ulasan_terkirim', true);
    }
}
