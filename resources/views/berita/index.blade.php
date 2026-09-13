@extends('layouts.halaman')

@section('judul', 'Berita & kegiatan')

@push('meta')
    <meta name="description" content="Kabar terbaru dari unit usaha dan kegiatan ekonomi Pondok Pesantren Abdussalam, Kubu Raya.">
@endpush

@section('isi')
    <div class="mx-auto max-w-6xl">
        <div class="mx-auto max-w-2xl text-center">
            <span class="ubin-hijau mx-auto mb-6 h-14 w-14 text-white"><x-ikon name="koran" class="h-7 w-7" /></span>
            <h1 class="judul-besar text-5xl sm:text-7xl">Berita &amp; kegiatan</h1>
            <p class="mx-auto mt-5 max-w-lg text-lg text-redup">Kabar terbaru dari unit usaha dan kegiatan ekonomi pesantren.</p>
            @auth
                <a href="{{ route('home') }}#berita" class="btn-admin mt-6">
                    <x-ikon name="pensil" class="h-4 w-4" /> Kelola berita di beranda
                </a>
            @endauth
        </div>

        @if ($beritas->isEmpty())
            <div class="mx-auto mt-14 max-w-md rounded-3xl border border-dashed border-garis px-6 py-12 text-center text-redup">
                Belum ada berita yang diterbitkan.
            </div>
        @else
            <div class="mt-14 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($beritas as $berita)
                    <x-kartu-berita :berita="$berita" />
                @endforeach
            </div>

            @if ($beritas->hasPages())
                <nav class="mt-12 flex items-center justify-center gap-3" aria-label="Halaman berita">
                    @if ($beritas->onFirstPage())
                        <span class="btn-putih pointer-events-none opacity-40"><x-ikon name="kiri" class="h-4 w-4" /> Sebelumnya</span>
                    @else
                        <a href="{{ $beritas->previousPageUrl() }}" class="btn-putih"><x-ikon name="kiri" class="h-4 w-4" /> Sebelumnya</a>
                    @endif

                    <span class="text-sm text-redup">Halaman {{ $beritas->currentPage() }} dari {{ $beritas->lastPage() }}</span>

                    @if ($beritas->hasMorePages())
                        <a href="{{ $beritas->nextPageUrl() }}" class="btn-putih">Berikutnya <x-ikon name="kanan" class="h-4 w-4" /></a>
                    @else
                        <span class="btn-putih pointer-events-none opacity-40">Berikutnya <x-ikon name="kanan" class="h-4 w-4" /></span>
                    @endif
                </nav>
            @endif
        @endif
    </div>
@endsection
