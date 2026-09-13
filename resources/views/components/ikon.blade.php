{{-- Ikon garis sederhana. <x-ikon name="pensil" class="h-4 w-4" /> --}}
@props(['name'])

@php
    $paths = [
        'pensil'  => '<path d="M4 20h4L19 9a2.83 2.83 0 0 0-4-4L4 16v4Z"/><path d="m13.5 6.5 4 4"/>',
        'hapus'   => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'tambah'  => '<path d="M12 5v14M5 12h14"/>',
        'pin'     => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
        'centang' => '<path d="m5 12.5 4.5 4.5L19 7"/>',
        'menu'    => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'tutup'   => '<path d="M6 6l12 12M18 6 6 18"/>',
        'chat'    => '<path d="M20 12a8 8 0 0 1-11.6 7.1L4 20l1-4.2A8 8 0 1 1 20 12Z"/>',
        'peta'    => '<path d="m9 4-6 2v14l6-2 6 2 6-2V4l-6 2-6-2Z"/><path d="M9 4v14M15 6v14"/>',
        'kompas'  => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5 5-2Z"/>',
        'toko'    => '<path d="M4 9h16l-1.5-5h-13L4 9Z"/><path d="M5 9v11h14V9M9 20v-6h6v6"/>',
        'jabat'   => '<path d="m11 17 2 2a1.4 1.4 0 0 0 2-2"/><path d="m14 14 2.5 2.5a1.4 1.4 0 0 0 2-2L15 11l-3 1-2.5-2.5L13 6l3 1 5 5M3 11l5-5 3 1"/><path d="m3 11 6 6a1.4 1.4 0 0 0 2-2"/>',
        'bintang' => '<path d="m12 2.8 2.8 5.9 6.4.8-4.7 4.4 1.2 6.4L12 17.2l-5.7 3.1 1.2-6.4L2.8 9.5l6.4-.8L12 2.8Z"/>',
        'piring'  => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="4"/>',
        'koran'   => '<path d="M5 4h12v15a1 1 0 0 0 1 1H6a2 2 0 0 1-2-2V5a1 1 0 0 1 1-1Z"/><path d="M17 8h2a1 1 0 0 1 1 1v9a2 2 0 0 1-2 2M8 8h5M8 12h5M8 16h3"/>',
        'kanan'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'kiri'    => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'kalender'=> '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M9 3v4M15 3v4"/>',
        'jam'     => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'foto'    => '<rect x="3.5" y="5" width="17" height="14" rx="2.5"/><circle cx="9" cy="10" r="1.8"/><path d="m20.5 16-5-5-8.5 8"/>',
        'geser'   => '<path d="M12 3v18M3 12h18"/><path d="m9 6 3-3 3 3M9 18l3 3 3-3M6 9l-3 3 3 3M18 9l3 3-3 3"/>',
        'tautan'  => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
        'masjid'  => '<path d="M12 3c-1.5 1.8-4 3-4 5.5a4 4 0 0 0 8 0C16 6 13.5 4.8 12 3Z"/><path d="M5 21v-8M19 21v-8M3 21h18M8 21v-5a4 4 0 0 1 8 0v5"/>',
    ];
@endphp

{{-- class dari pemanggil menggantikan ukuran bawaan (bukan digabung) agar h-4 tidak kalah oleh h-5 --}}
<svg {{ $attributes->except('class') }} class="{{ $attributes->get('class') ?: 'h-5 w-5' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths[$name] ?? '' !!}</svg>
