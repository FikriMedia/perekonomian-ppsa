{{-- Tata letak halaman di luar beranda (daftar & detail berita). --}}
@php
    $pengaturanUmum = \App\Models\Pengaturan::ambil(['whatsapp']);
    $waUtama = \App\Models\Pengaturan::whatsappUrl(
        $pengaturanUmum['whatsapp'] ?? null,
        "Assalamu'alaikum, saya ingin bertanya tentang unit usaha Pondok Pesantren Abdussalam."
    );
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <title>@yield('judul') | Perekonomian PPSA</title>
    @stack('meta')
</head>
<body>
    <header class="sticky top-3 z-50 px-3 sm:top-4">
        <nav class="mx-auto flex max-w-5xl items-center gap-2 rounded-full border border-garis/80 bg-white/85 p-1.5 pl-2.5 shadow-[0_12px_32px_-20px_rgba(20,35,26,.4)] backdrop-blur-md sm:pl-3" aria-label="Utama">
            <a href="{{ route('home') }}" class="flex min-w-0 items-center gap-2.5">
                <img src="{{ asset('images/logo/perkom.png') }}" alt="" class="h-9 w-9 shrink-0 object-contain">
                <span class="font-display text-[13px] font-semibold leading-[1.15] tracking-[-0.02em] sm:text-[14px]">
                    <span class="block">Perekonomian Pondok</span>
                    <span class="block">Pesantren Abdussalam</span>
                </span>
            </a>

            <div class="ml-auto flex items-center gap-0.5">
                <a href="{{ route('home') }}" class="hidden rounded-full px-3 py-2 text-[15px] font-medium text-tinta/75 transition hover:bg-kanvas hover:text-tinta sm:block">Beranda</a>
                <a href="{{ route('berita.index') }}" @class([
                    'rounded-full px-3 py-2 text-[15px] font-medium transition',
                    'bg-hijau-muda text-hijau-tua shadow-[inset_0_0_0_1px_rgba(47,138,52,.18)]' => request()->routeIs('berita.*'),
                ])>Berita</a>
                @if ($waUtama)
                    <a href="{{ $waUtama }}" target="_blank" rel="noopener" class="btn-gelap ml-1 hidden shrink-0 py-2 md:inline-flex">Chat WhatsApp</a>
                @endif
            </div>
        </nav>
    </header>

    @if (session('status'))
        <div x-data="{ tampil: true }" x-init="setTimeout(() => tampil = false, 4000)" x-show="tampil" x-transition.opacity.duration.300ms
             class="pointer-events-none fixed inset-x-0 top-20 z-[80] flex justify-center px-4" role="status">
            <p class="pointer-events-auto flex items-center gap-2 rounded-full bg-tinta px-5 py-2.5 text-sm font-medium text-white shadow-lg">
                <x-ikon name="centang" class="h-4 w-4 text-emas" /> {{ session('status') }}
            </p>
        </div>
    @endif

    <main class="px-4 pb-24 pt-12 sm:pb-32 sm:pt-16">
        @yield('isi')
    </main>

    <footer class="px-4 pb-10">
        <div class="kartu mx-auto flex max-w-6xl flex-col gap-6 p-8 sm:flex-row sm:items-center sm:justify-between sm:p-10">
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/logo/perkom.png') }}" alt="" class="h-14 w-14 object-contain">
                <div>
                    <p class="font-display text-lg font-semibold tracking-[-0.02em]">Perekonomian Pondok Pesantren Abdussalam</p>
                    <p class="text-[15px] text-redup">Kubu Raya, Kalimantan Barat</p>
                </div>
            </div>
            <a href="{{ route('home') }}" class="btn-putih self-start sm:self-auto"><x-ikon name="kiri" class="h-4 w-4" /> Kembali ke beranda</a>
        </div>
        <div class="mx-auto mt-6 max-w-6xl px-2 text-sm text-redup">
            <p>&copy; {{ now()->year }} Pondok Pesantren Abdussalam</p>
        </div>
    </footer>
</body>
</html>
