{{--
    Kartu berita. Varian:
      - 'besar'   : foto penuh dengan teks di atas gradasi gelap (berita utama di beranda)
      - 'mendatar': foto kecil di kiri, teks di kanan (berita pendamping di beranda)
      - 'tegak'   : foto di atas, teks di bawah (grid halaman berita)
--}}
@props(['berita', 'varian' => 'tegak'])

@php
    $tanggal = $berita->terbit_pada->translatedFormat('j F Y');
    $url = route('berita.show', $berita);
@endphp

@if ($varian === 'besar')
    <a href="{{ $url }}" {{ $attributes->class('group relative flex min-h-[22rem] flex-col justify-end overflow-hidden rounded-[28px] bg-tinta text-white sm:min-h-[26rem]') }}>
        @if ($berita->foto_url)
            <img src="{{ $berita->foto_url }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition duration-700 ease-out group-hover:scale-[1.04]">
        @else
            <div class="absolute inset-0 bg-[radial-gradient(120%_90%_at_20%_0%,#8BDB5E_0%,#3FA23A_45%,#1C5520_100%)]"></div>
            <x-ikon name="koran" class="absolute right-8 top-8 h-24 w-24 text-white/15" />
        @endif
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>

        <div class="relative p-6 sm:p-9">
            <p class="flex items-center gap-2 text-sm font-medium text-white/80">
                <span class="rounded-full bg-white/15 px-2.5 py-0.5 text-xs font-semibold text-white backdrop-blur">Terbaru</span>
                {{ $tanggal }}
            </p>
            <h3 class="mt-3 max-w-2xl font-display text-[28px] font-semibold leading-[1.1] tracking-rapat sm:text-4xl">{{ $berita->judul }}</h3>
            <p class="mt-3 line-clamp-2 max-w-xl text-[15px] leading-relaxed text-white/80 sm:text-base">{{ $berita->cuplikan }}</p>
            <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold">
                Baca selengkapnya <x-ikon name="kanan" class="h-4 w-4 transition group-hover:translate-x-1" />
            </span>
        </div>
    </a>
@elseif ($varian === 'mendatar')
    <a href="{{ $url }}" {{ $attributes->class('kartu group flex gap-4 overflow-hidden p-3 transition hover:border-tinta/20 hover:shadow-[0_18px_40px_-24px_rgba(20,35,26,.35)] sm:gap-5') }}>
        <div class="relative aspect-square w-28 shrink-0 overflow-hidden rounded-[20px] bg-hijau-muda sm:w-36">
            @if ($berita->foto_url)
                <img src="{{ $berita->foto_url }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            @else
                <div class="grid h-full place-items-center text-hijau"><x-ikon name="koran" class="h-9 w-9" /></div>
            @endif
        </div>
        <div class="flex min-w-0 flex-col py-1.5 pr-2">
            <p class="text-[13px] font-medium text-redup">{{ $tanggal }}</p>
            <h3 class="mt-1.5 line-clamp-2 font-display text-lg font-semibold leading-snug tracking-[-0.02em] sm:text-xl">{{ $berita->judul }}</h3>
            <p class="mt-1.5 line-clamp-2 text-sm leading-relaxed text-redup">{{ $berita->cuplikan }}</p>
        </div>
    </a>
@else
    <a href="{{ $url }}" {{ $attributes->class('kartu group flex flex-col overflow-hidden transition hover:border-tinta/20 hover:shadow-[0_18px_40px_-24px_rgba(20,35,26,.35)]') }}>
        <div class="relative aspect-[16/10] overflow-hidden bg-hijau-muda">
            @if ($berita->foto_url)
                <img src="{{ $berita->foto_url }}" alt="" loading="lazy" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
            @else
                <div class="grid h-full place-items-center text-hijau"><x-ikon name="koran" class="h-10 w-10" /></div>
            @endif
        </div>
        <div class="flex flex-1 flex-col p-6">
            <p class="text-[13px] font-medium text-redup">{{ $tanggal }}</p>
            <h3 class="mt-2 line-clamp-2 font-display text-xl font-semibold leading-snug tracking-[-0.02em]">{{ $berita->judul }}</h3>
            <p class="mt-2 line-clamp-3 text-[15px] leading-relaxed text-redup">{{ $berita->cuplikan }}</p>
            <span class="mt-auto inline-flex items-center gap-1.5 pt-5 text-sm font-semibold text-hijau">
                Baca <x-ikon name="kanan" class="h-4 w-4 transition group-hover:translate-x-1" />
            </span>
        </div>
    </a>
@endif
