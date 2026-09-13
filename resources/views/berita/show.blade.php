@extends('layouts.halaman')

@section('judul', $berita->judul)

@push('meta')
    {{-- Pratinjau saat link dibagikan ke WhatsApp / Facebook --}}
    <meta name="description" content="{{ $berita->cuplikan }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $berita->judul }}">
    <meta property="og:description" content="{{ $berita->cuplikan }}">
    <meta property="og:url" content="{{ route('berita.show', $berita) }}">
    <meta property="og:image" content="{{ $berita->foto_url ?? asset('images/logo/perkom.png') }}">
    <meta name="twitter:card" content="{{ $berita->foto_url ? 'summary_large_image' : 'summary' }}">
@endpush

@section('isi')
    @php
        $urlBerita = route('berita.show', $berita);
        $bagikanWa = 'https://wa.me/?text=' . rawurlencode($berita->judul . ' ' . $urlBerita);
    @endphp

    <article class="mx-auto max-w-3xl">
        <a href="{{ route('berita.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-redup transition hover:text-tinta">
            <x-ikon name="kiri" class="h-4 w-4" /> Semua berita
        </a>

        <header class="mt-8">
            <p class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[15px] text-redup">
                <span class="inline-flex items-center gap-1.5"><x-ikon name="kalender" class="h-4 w-4" /> {{ $berita->terbit_pada->translatedFormat('l, j F Y') }}</span>
                <span class="inline-flex items-center gap-1.5"><x-ikon name="jam" class="h-4 w-4" /> {{ $berita->menit_baca }} menit baca</span>
            </p>
            <h1 class="judul-besar mt-4 text-[40px] sm:text-6xl">{{ $berita->judul }}</h1>
            @if ($berita->ringkasan)
                <p class="mt-6 text-xl leading-relaxed text-redup">{{ $berita->ringkasan }}</p>
            @endif

            @auth
                <a href="{{ route('home') }}#kelola-berita" class="btn-admin mt-6">
                    <x-ikon name="pensil" class="h-4 w-4" /> Edit di beranda
                </a>
            @endauth
        </header>

        @if ($berita->foto_url)
            <figure class="mt-10 overflow-hidden rounded-[28px] border border-garis bg-white sm:-mx-10">
                <img src="{{ $berita->foto_url }}" alt="{{ $berita->judul }}" class="aspect-video w-full object-cover">
            </figure>
        @endif

        <div class="mt-10 space-y-6 text-[18px] leading-[1.8] text-tinta/85">
            @foreach ($berita->paragraf as $paragraf)
                <p class="whitespace-pre-line">{{ $paragraf }}</p>
            @endforeach
        </div>

        {{-- Bagikan --}}
        <div class="mt-12 flex flex-wrap items-center gap-3 border-t border-garis pt-8"
             x-data="{ tersalin: false }">
            <p class="mr-auto font-semibold">Bagikan berita ini</p>
            <a href="{{ $bagikanWa }}" target="_blank" rel="noopener" class="btn-wa">
                <x-ikon name="chat" class="h-4 w-4" /> WhatsApp
            </a>
            <button type="button" class="btn-putih"
                    @click="navigator.clipboard?.writeText(@js($urlBerita)).then(() => { tersalin = true; setTimeout(() => tersalin = false, 2000) })">
                <x-ikon name="tautan" class="h-4 w-4" />
                <span x-text="tersalin ? 'Tautan disalin' : 'Salin tautan'">Salin tautan</span>
            </button>
        </div>
    </article>

    @if ($lainnya->isNotEmpty())
        <section class="mx-auto mt-24 max-w-6xl" aria-labelledby="berita-lainnya">
            <div class="text-center">
                <h2 id="berita-lainnya" class="judul-section">Berita lainnya</h2>
                <a href="{{ route('berita.index') }}" class="btn-putih mt-6">Semua berita <x-ikon name="kanan" class="h-4 w-4" /></a>
            </div>
            <div class="mt-10 flex flex-wrap justify-center gap-4">
                @foreach ($lainnya as $item)
                    <x-kartu-berita :berita="$item" class="w-full sm:w-[calc((100%-1rem)/2)] lg:w-[calc((100%-2rem)/3)]" />
                @endforeach
            </div>
        </section>
    @endif
@endsection
