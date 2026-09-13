{{-- Aset bersama: font, Tailwind (play CDN), Alpine + plugin focus, token desain. --}}
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" type="image/png" href="{{ asset('images/logo/favicon.png') }}">

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@500;600;700&family=Urbanist:wght@400;500;600;700&display=swap" rel="stylesheet">

<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    kanvas: '#EEF0F3',   // latar halaman (abu dingin, seperti referensi)
                    garis:  '#DEE2E7',   // border kartu
                    tinta:  '#14231A',   // teks utama, hitam kehijauan
                    redup:  '#6E7680',   // teks sekunder
                    hijau:  { DEFAULT: '#2F8A34', tua: '#236B28', muda: '#E6F3E4' }, // dari logo pesantren
                    emas:   { DEFAULT: '#F2B705', muda: '#FFF6D6', tua: '#6B4E00' },  // bintang logo & mode edit
                    wa:     '#1FA855',
                },
                fontFamily: {
                    display: ['"Inter Tight"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    sans:    ['Urbanist', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                },
                letterSpacing: { rapat: '-0.035em' },
            },
        },
    };
</script>

<style>
    /* Sengaja TIDAK memakai efek transisi antarhalaman (CSS view transition): di sebagian perangkat Chrome/Edge
       tab crash ("Terjadi masalah") saat pindah dari halaman berita ke beranda yang berat efek visualnya. */

    /* Smooth scroll baru aktif setelah halaman dimuat, supaya lompatan ke #bagian
       sesudah reload terjadi instan, bukan di-scroll pelan dari atas. */
    html.halus { scroll-behavior: smooth; }
    @media (prefers-reduced-motion: reduce) { html.halus { scroll-behavior: auto; } }
</style>
<script>
    addEventListener('load', () => requestAnimationFrame(() => requestAnimationFrame(() => document.documentElement.classList.add('halus'))));
</script>

<style type="text/tailwindcss">
    @layer base {
        body { @apply bg-kanvas font-sans text-tinta antialiased; }
        [x-cloak] { display: none !important; }
        :focus-visible { @apply outline-none ring-2 ring-hijau ring-offset-2 ring-offset-kanvas; }
        section[id] { scroll-margin-top: 6.5rem; }
    }

    @layer components {
        /* Judul besar: rapat & tegas seperti referensi */
        .judul-besar { @apply font-display font-semibold tracking-rapat leading-[0.98] text-tinta; }
        .judul-section { @apply font-display text-4xl font-semibold tracking-rapat leading-[1.05] sm:text-5xl; }

        .btn { @apply inline-flex items-center justify-center gap-2 rounded-full px-5 py-2.5 text-[15px] font-semibold transition active:scale-[.98] disabled:opacity-50; }
        .btn-gelap { @apply btn bg-tinta text-white hover:bg-black; }
        .btn-hijau { @apply btn bg-hijau text-white shadow-[inset_0_1px_0_rgba(255,255,255,.25),0_8px_20px_-8px_rgba(47,138,52,.7)] hover:bg-hijau-tua; }
        .btn-putih { @apply btn border border-garis bg-white text-tinta hover:border-tinta/30; }
        .btn-wa    { @apply btn bg-wa text-white hover:brightness-95; }

        /* Kontrol admin: kuning emas agar jelas beda dari UI publik */
        .btn-admin  { @apply inline-flex items-center gap-1.5 rounded-full border border-emas bg-emas-muda px-3 py-1.5 text-[13px] font-semibold text-emas-tua transition hover:bg-emas hover:text-tinta; }
        .btn-admin-hapus { @apply btn-admin border-red-300 bg-red-50 text-red-700 hover:bg-red-600 hover:text-white; }
        .ikon-admin { @apply grid h-8 w-8 place-items-center rounded-full border border-emas bg-emas-muda text-emas-tua shadow-sm transition hover:bg-emas hover:text-tinta; }
        .ikon-admin-hapus { @apply ikon-admin border-red-300 bg-red-50 text-red-700 hover:bg-red-600 hover:text-white; }

        .label { @apply mb-1.5 block text-sm font-semibold text-tinta; }
        .field { @apply block w-full rounded-2xl border border-garis bg-white px-4 py-3 text-[15px] text-tinta placeholder:text-redup/70 transition focus:border-hijau focus:outline-none focus:ring-4 focus:ring-hijau/15; }
        .bantuan { @apply mt-1.5 text-xs text-redup; }

        .kartu { @apply rounded-[28px] border border-garis bg-white; }

        /* Ubin squircle mengkilap ala ikon aplikasi di referensi */
        .ubin {
            @apply grid place-items-center rounded-[26%] bg-white;
            background-image: linear-gradient(180deg, #FFFFFF 0%, #F4F5F7 100%);
            box-shadow:
                inset 0 1px 0 #fff,
                0 1px 2px rgba(20, 35, 26, .06),
                0 14px 30px -12px rgba(20, 35, 26, .28);
        }
        .ubin-hijau {
            @apply grid place-items-center rounded-[26%];
            background-image: radial-gradient(120% 95% at 50% 0%, #8BDB5E 0%, #3FA23A 52%, #256F29 100%);
            box-shadow:
                inset 0 2px 1px rgba(255, 255, 255, .5),
                inset 0 -8px 16px rgba(0, 0, 0, .18),
                0 28px 50px -16px rgba(37, 111, 41, .6);
        }
    }
</style>

<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/focus@3.14.1/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.1/dist/cdn.min.js"></script>
