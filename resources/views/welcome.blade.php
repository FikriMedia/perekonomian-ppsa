@php
    $heroJudul = $pengaturan['hero_judul'] ?? 'Satu pesantren, banyak usaha';
    $heroDeskripsi = $pengaturan['hero_deskripsi'] ?? '';
    // Deskripsi yang sengaja dikosongkan admin tersimpan sebagai null, jadi cek keberadaan kunci (bukan ??).
    $cabangJudul = $pengaturan['cabang_judul'] ?? 'Cabang toserba';
    $cabangDeskripsi = $pengaturan->has('cabang_deskripsi')
        ? (string) $pengaturan['cabang_deskripsi']
        : 'Belanja kebutuhan harian di cabang terdekat, atau tanyakan stok lewat WhatsApp.';
    $latar = \App\Models\Pengaturan::latarHero($pengaturan['hero_background'] ?? null);
    $adaLatar = $latar['tipe'] !== 'bawaan';
    $visi = $pengaturan['visi'] ?? '';
    $misi = collect(preg_split('/\r\n|\r|\n/', $pengaturan['misi'] ?? ''))->map(fn ($baris) => trim($baris))->filter()->values();
    $waUtama = \App\Models\Pengaturan::whatsappUrl(
        $pengaturan['whatsapp'] ?? null,
        "Assalamu'alaikum, saya ingin bertanya tentang unit usaha Pondok Pesantren Abdussalam."
    );

    // Tab yang dibuka: dari flash controller, dari input lama saat validasi produk gagal, atau tab pertama.
    $tabAwal = session('tab');
    if (! $tabAwal && old('_modal') === 'produk') {
        $unitLama = $pilihanUnit->firstWhere('id', (int) old('unit_id'));
        $tabAwal = $unitLama?->parent_id ?? $unitLama?->id;
    }
    if (! $units->contains('id', $tabAwal)) {
        $tabAwal = $units->first()?->id;
    }
    $tabAwal = $tabAwal ? (int) $tabAwal : null;

    // Carousel 3D unit usaha: data ringkas untuk keterangan di bawah kartu.
    $dataUnit = $units->map(fn ($unit) => [
        'id'        => (int) $unit->id,
        'nama'      => $unit->nama_unit,
        'deskripsi' => $unit->deskripsi,
        'produk'    => $unit->produks->count() + $unit->children->sum(fn ($sub) => $sub->produks->count()),
    ])->values()->all();
    // Kartu digandakan hingga minimal 8 agar putaran tanpa ujung tidak pernah terlihat "melompat".
    $salinanUnit = $units->isEmpty() ? 0 : (int) ceil(8 / $units->count());

    // Ekosistem usaha di hero: logo unit (dari controller) di posisi yang dihitung App\Support\TataLetakHero
    // sesuai jumlah logo. Admin bisa menukar logo antarposisi; urutan ID unit tersimpan di 'hero_urutan_logo'.
    $slotHero = $tataHero['slot'];
    $idHero = $unitHero->pluck('id')->map(fn ($id) => (string) $id)->all();
    // Ukuran ubin: logo melebar selalu mendatar; logo persegi besar di posisi tengah kiri/kanan, kecil di posisi lain.
    // Lebih dari 6 logo: semua ubin diperkecil agar tetap lega.
    $ukuranUbin = $tataHero['padat']
        ? ['lebar' => 'h-10 w-28 px-2.5 !rounded-xl lg:h-12 lg:w-32', 'besar' => 'h-20 w-20 p-2 lg:h-24 lg:w-24', 'kecil' => 'h-14 w-14 p-1.5 lg:h-16 lg:w-16']
        : ['lebar' => 'h-12 w-32 px-3 !rounded-2xl lg:h-14 lg:w-40', 'besar' => 'h-24 w-24 p-2.5 lg:h-28 lg:w-28', 'kecil' => 'h-16 w-16 p-2 lg:h-20 lg:w-20'];
    $jenisUbin = fn ($unit, int $posisi) => $unit->logo_lebar ? 'lebar' : ($slotHero[$posisi]['besar'] ? 'besar' : 'kecil');

    // Marquee butuh cukup item agar lintasannya lebih lebar dari layar.
    $ulangMitra = $mitras->isEmpty() ? 0 : (int) max(1, ceil(10 / $mitras->count()));
    $ulangUlasan = $ulasans->isEmpty() ? 0 : (int) max(1, ceil(6 / $ulasans->count()));

    $jumlahUlasan = (int) ($statistikUlasan->jumlah ?? 0);
    $rataRating = $jumlahUlasan ? number_format((float) $statistikUlasan->rata_rata, 1, ',', '.') : null;
    $jumlahPending = $ulasanAdmin->where('status', 'pending')->count();

    // Urutan menu harus sama dengan urutan section di halaman (dipakai penanda menu aktif).
    $menu = [
        '#visi-misi'    => 'Visi & misi',
        '#unit'         => 'Unit usaha',
        '#cabang'       => 'Cabang',
        '#berita'       => 'Berita',
        '#mitra'        => 'Mitra',
        '#tentang-kami' => 'Tentang kami',
        '#ulasan'       => 'Ulasan',
    ];

    // Section mitra & berita hanya tampil untuk pengunjung jika sudah ada datanya; admin tetap melihatnya untuk menambah.
    $tampilkanMitra = $mitras->isNotEmpty() || auth()->check();
    if (! $tampilkanMitra) {
        unset($menu['#mitra']);
    }
    $tampilkanBerita = $beritas->isNotEmpty() || auth()->check();
    if (! $tampilkanBerita) {
        unset($menu['#berita']);
    }

    // Tentang kami
    $tentangJudul = $pengaturan['tentang_judul'] ?? 'Tumbuh bersama santri dan warga';
    $tentangIsi = $pengaturan['tentang_isi'] ?? 'Perekonomian Pondok Pesantren Abdussalam mengelola unit usaha pesantren di Kubu Raya, Kalimantan Barat. Hasil usahanya menopang kemandirian pesantren, sekaligus menjadi tempat santri belajar berwirausaha secara langsung.';
    // Isian angka untuk form admin: selalu 3 baris, kosong jika memakai angka otomatis.
    $statistikIsian = collect(json_decode($pengaturan['tentang_statistik'] ?? '', true) ?: [])
        ->pad(3, ['angka' => '', 'label' => ''])->take(3)->values()->all();

    // Posisi foto melayang (desktop), diisi bergantian kiri-kanan agar tetap seimbang walau fotonya sedikit.
    // [posisi, ukuran, rotasi (derajat), jeda animasi (detik)]
    $slotFoto = [
        ['left: 1%; top: 3%',    'h-40 w-32', -6, 0],
        ['right: 13%; top: 30%', 'h-32 w-28', 5, .9],
        ['left: 3%; top: 60%',   'h-40 w-36', 4, 1.8],
        ['right: 1%; top: 2%',   'h-36 w-28', 6, .4],
        ['left: 14%; top: 33%',  'h-28 w-24', -4, 1.3],
        ['right: 3%; top: 63%',  'h-36 w-32', -5, 2.2],
    ];
    // Belum ada foto: logo unit usaha yang melayang, agar section tidak terlihat kosong.
    $logoMelayang = $units->filter(fn ($unit) => $unit->logo_url)->take(6)->values();
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head')
    <title>Perekonomian PPSA</title>
    <meta name="description" content="{{ $heroDeskripsi }}">

    <script>
        // Kembali dari halaman lain di situs ini (mis. berita): langsung tampil tanpa animasi pembuka.
        try {
            const asal = document.referrer ? new URL(document.referrer) : null;
            if (asal && asal.origin === location.origin && asal.pathname !== location.pathname) {
                document.documentElement.classList.add('tanpa-intro');
            }
        } catch (e) {}
    </script>

    <style>
        /* ---- Hero: satu momen animasi saat halaman dibuka ---- */
        .ubin-pusat {
            display: grid;
            place-items: center;
            border-radius: 26%;
            background-image: linear-gradient(180deg, #FFFFFF 0%, #F0F4F0 100%);
            box-shadow:
                inset 0 1px 0 #fff,
                0 0 0 1px rgba(47, 138, 52, .14),
                0 0 0 12px rgba(47, 138, 52, .06),
                0 34px 60px -20px rgba(37, 111, 41, .5);
        }
        .hero-garis path { stroke-dasharray: 1; stroke-dashoffset: 1; animation: gambar-garis 1.1s ease-out .1s forwards; }
        .hero-garis circle { opacity: 0; animation: pudar .4s ease-out 1s forwards; }
        .hero-ubin { animation: muncul .7s cubic-bezier(.2, .9, .3, 1.25) both; animation-delay: var(--d, 0s); }
        .blur-masuk { animation: blur-masuk .9s cubic-bezier(.2, .7, .2, 1) both; animation-delay: var(--d, 0s); }

        @keyframes gambar-garis { to { stroke-dashoffset: 0; } }
        @keyframes pudar { to { opacity: 1; } }
        @keyframes muncul { from { opacity: 0; transform: scale(.55); } to { opacity: 1; transform: scale(1); } }
        @keyframes blur-masuk { from { opacity: 0; filter: blur(14px); transform: translateY(10px); } to { opacity: 1; filter: blur(0); transform: none; } }

        /* ---- Hero dengan background gelap: teks & garis dibuat terang ---- */
        .hero-terang .judul-besar { color: #fff; text-shadow: 0 2px 28px rgba(0, 0, 0, .28); }
        .hero-terang .hero-deskripsi { color: rgba(255, 255, 255, .88); text-shadow: 0 1px 14px rgba(0, 0, 0, .3); }
        .hero-terang .hero-garis g[stroke] { stroke: rgba(255, 255, 255, .55); }
        .hero-terang .hero-garis g[fill] { fill: #fff; }

        /* ---- Infinite marquee (mitra & ulasan) ---- */
        .marquee {
            overflow: hidden;
            -webkit-mask-image: linear-gradient(to right, transparent, #000 7%, #000 93%, transparent);
                    mask-image: linear-gradient(to right, transparent, #000 7%, #000 93%, transparent);
        }
        .marquee-track { display: flex; width: max-content; animation: geser-kiri var(--durasi, 40s) linear infinite; }
        .marquee:hover .marquee-track,
        .marquee:focus-within .marquee-track { animation-play-state: paused; }
        @keyframes geser-kiri { to { transform: translateX(-50%); } }

        /* Animasi pembuka tidak diputar ulang setiap reload sesudah admin menyimpan data. */
        .tanpa-intro .hero-garis path { stroke-dashoffset: 0; animation: none; }
        .tanpa-intro .hero-garis circle { opacity: 1; animation: none; }
        .tanpa-intro .hero-ubin, .tanpa-intro .blur-masuk { animation: none; }

        /* ---- Carousel 3D unit usaha: kartu tersusun di dinding silinder ---- */
        .panggung-unit {
            perspective: 1100px;
            touch-action: pan-y;
            -webkit-mask-image: linear-gradient(to right, transparent, #000 12%, #000 88%, transparent);
                    mask-image: linear-gradient(to right, transparent, #000 12%, #000 88%, transparent);
        }
        .kartu-unit {
            transition: transform .75s cubic-bezier(.22, .8, .2, 1), opacity .5s ease;
            will-change: transform;
        }
        .kartu-unit .isi {
            background-image: linear-gradient(180deg, #FFFFFF 0%, #F4F5F7 100%);
            box-shadow: inset 0 1px 0 #fff, 0 1px 2px rgba(20, 35, 26, .06), 0 18px 34px -16px rgba(20, 35, 26, .35);
            transition: box-shadow .5s ease, border-color .5s ease;
        }
        .kartu-unit.aktif .isi {
            border-color: rgba(47, 138, 52, .45);
            box-shadow: inset 0 1px 0 #fff, 0 0 0 4px rgba(47, 138, 52, .1), 0 26px 44px -18px rgba(37, 111, 41, .55);
        }
        .keterangan-masuk { animation: blur-masuk .55s cubic-bezier(.2, .7, .2, 1) both; }

        /* ---- Tentang kami: foto melayang pelan di sekitar teks ---- */
        .foto-melayang { animation: mengapung 7s ease-in-out infinite; animation-delay: var(--d, 0s); }
        @keyframes mengapung { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }

        @media (prefers-reduced-motion: reduce) {
            .hero-garis path { stroke-dashoffset: 0; animation: none; }
            .hero-garis circle { opacity: 1; animation: none; }
            .hero-ubin, .blur-masuk { animation: none; }
            .marquee { overflow-x: auto; }
            .marquee-track { animation: none; }
            .kartu-unit, .kartu-unit .isi { transition: none; }
            .keterangan-masuk { animation: none; }
            .foto-melayang { animation: none; }
        }
    </style>

    <script>
        document.addEventListener('alpine:init', () => {
            const kosong = {
                hero:   { hero_judul: '', hero_deskripsi: '', whatsapp: '' },
                latar:  { ...@js(\App\Models\Pengaturan::LATAR_HERO), file_url: null, file_tipe: null },
                visi:   { visi: '', misi: '' },
                unit:   { id: '', nama_unit: '', deskripsi: '', parent_id: '', logo_url: null, logo_upload: false, tampil_hero: true },
                produk: { id: '', unit_id: '', nama_produk: '', harga: '', deskripsi: '', foto_url: null },
                cabang: { id: '', nama_cabang: '', alamat: '', no_whatsapp: '', link_maps: '', foto_url: null },
                teksCabang: { cabang_judul: '', cabang_deskripsi: '' },
                mitra:  { id: '', nama_mitra: '', logo_url: null },
                berita: { id: '', judul: '', ringkasan: '', isi: '', terbit_pada: @js(now()->toDateString()), foto_url: null },
                tentang: { tentang_judul: '', tentang_isi: '', statistik: [{ angka: '', label: '' }, { angka: '', label: '' }, { angka: '', label: '' }] },
                fotoTentang: {},
                hapus:  { jenis: '', label: '', action: '', catatan: '' },
            };

            Alpine.store('modal', {
                name: null,
                data: {},
                open(name, data = {}) {
                    this.data = { ...(kosong[name] ?? {}), ...JSON.parse(JSON.stringify(data)) };
                    this.name = name;
                },
                close() {
                    this.name = null;
                },
            });

            @auth
            /*
             * Admin: tukar posisi logo di hero. Seret logo ke posisi logo lain (atau klik dua logo),
             * keduanya bertukar tempat. 6 posisinya tetap, sehingga garis penghubung selalu pas.
             */
            Alpine.data('aturLogo', (urutan, bawaanUrutan, slot, lebar, ukuran) => ({
                urutan: [...urutan],
                asli: [...urutan],
                aktif: false,
                pilih: null,     // logo yang dipilih lewat klik
                seret: null,     // { kunci, x0, y0, dx, dy, gerak }
                sasaran: null,   // indeks posisi tujuan saat diseret
                get berubah() { return this.urutan.join() !== this.asli.join(); },
                posisi(k) { return this.urutan.indexOf(k); },
                mulaiAtur() {
                    this.aktif = true;
                    this.$nextTick(() => this.$refs.panggungLogo?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
                },
                batal() { this.urutan = [...this.asli]; this.aktif = false; this.pilih = null; this.seret = null; this.sasaran = null; },
                bawaan() { this.urutan = [...bawaanUrutan]; this.pilih = null; },
                tukar(i, j) {
                    if (i === j || i < 0 || j < 0) return;
                    const baru = [...this.urutan];
                    [baru[i], baru[j]] = [baru[j], baru[i]];
                    this.urutan = baru;
                },
                gayaWadah(k) {
                    const s = slot[this.posisi(k)];
                    const g = this.seret?.kunci === k && this.seret.gerak ? this.seret : null;
                    return {
                        left: s.x + '%',
                        top: s.y + '%',
                        translate: g ? `${g.dx}px ${g.dy}px` : '0 0',
                        zIndex: g ? 30 : (this.aktif ? 20 : ''),
                    };
                },
                kelasWadah(k) {
                    const diseret = this.seret?.kunci === k && this.seret.gerak;
                    return [
                        this.aktif ? (diseret ? 'cursor-grabbing' : 'cursor-grab') : '',
                        diseret ? '' : 'transition-[left,top,translate] duration-500 ease-[cubic-bezier(.2,.8,.2,1)]',
                    ].join(' ');
                },
                kelasUbin(k) {
                    const i = this.posisi(k);
                    const jenis = lebar[k] ? 'lebar' : (slot[i].besar ? 'besar' : 'kecil');
                    let tanda = '';
                    if (this.aktif) {
                        tanda = 'outline-dashed outline-2 outline-offset-4 outline-emas';
                        if (this.pilih === k || (this.seret?.kunci === k && this.seret.gerak)) tanda = 'outline outline-[3px] outline-offset-4 outline-hijau';
                        else if (this.sasaran === i && this.seret?.gerak) tanda = 'outline outline-[3px] outline-offset-4 outline-hijau/60';
                    }
                    return ukuran[jenis] + ' ' + tanda;
                },
                mulai(e, k) {
                    if (! this.aktif || e.button > 0) return;
                    e.preventDefault();
                    this.seret = { kunci: k, x0: e.clientX, y0: e.clientY, dx: 0, dy: 0, gerak: false };
                },
                gerak(e) {
                    const g = this.seret;
                    if (! g) return;
                    g.dx = e.clientX - g.x0;
                    g.dy = e.clientY - g.y0;
                    if (! g.gerak && Math.hypot(g.dx, g.dy) > 6) { g.gerak = true; this.pilih = null; }
                    if (! g.gerak) return;

                    // Posisi terdekat dari kursor (dalam piksel panggung), maksimal ~10% lebar panggung.
                    const r = this.$refs.panggungLogo.getBoundingClientRect();
                    const px = e.clientX - r.left, py = e.clientY - r.top;
                    let terdekat = null, jarak = r.width * 0.10;
                    slot.forEach((s, i) => {
                        const d = Math.hypot(px - s.x / 100 * r.width, py - s.y / 100 * r.height);
                        if (d < jarak) { jarak = d; terdekat = i; }
                    });
                    this.sasaran = terdekat !== this.posisi(g.kunci) ? terdekat : null;
                },
                lepas() {
                    const g = this.seret;
                    if (! g) return;
                    if (g.gerak) {
                        if (this.sasaran !== null) this.tukar(this.posisi(g.kunci), this.sasaran);
                    } else if (this.pilih === null) {
                        this.pilih = g.kunci;
                    } else {
                        this.tukar(this.posisi(this.pilih), this.posisi(g.kunci));
                        this.pilih = null;
                    }
                    this.seret = null;
                    this.sasaran = null;
                },
            }));
            @endauth

            // Menu navbar menyala sesuai bagian yang sedang terlihat di layar.
            Alpine.data('navAktif', (hrefs) => ({
                buka: false,
                aktif: null,
                kunci: false,
                init() {
                    const bagian = hrefs.map((h) => document.querySelector(h)).filter(Boolean);
                    let antre = false;
                    const cek = () => {
                        antre = false;
                        if (this.kunci || ! bagian.length) return;
                        const batas = window.innerHeight * 0.4;
                        let kini = null;
                        for (const el of bagian) {
                            if (el.getBoundingClientRect().top <= batas) kini = '#' + el.id;
                        }
                        // Bagian terakhir sering terlalu pendek untuk melewati batas; paksa aktif saat sudah di dasar.
                        if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 4) {
                            kini = '#' + bagian[bagian.length - 1].id;
                        }
                        this.aktif = kini;
                    };
                    const jadwalkan = () => { if (! antre) { antre = true; requestAnimationFrame(cek); } };
                    addEventListener('scroll', jadwalkan, { passive: true });
                    addEventListener('resize', jadwalkan);
                    addEventListener('scrollend', () => { this.kunci = false; jadwalkan(); });
                    cek();
                },
                pilih(href) {
                    // Langsung tandai menu yang diklik, dan jangan berkedip melewati bagian lain selama scroll.
                    this.aktif = href;
                    this.buka = false;
                    this.kunci = true;
                    clearTimeout(this._lepas);
                    this._lepas = setTimeout(() => { this.kunci = false; }, 1200);
                },
            }));

            /*
             * Carousel 3D unit usaha. Kartu ditata di dinding silinder yang dilihat dari dalam:
             * kartu aktif di tengah, kartu di sisi lebih dekat ke mata dan miring ke arah tengah.
             * Setiap unit digandakan (salinan) agar putaran tanpa ujung; `posisi` terus bertambah/berkurang.
             */
            Alpine.data('karuselUnit', (units, awal, salinan) => ({
                units,
                salinan,
                aktif: awal,
                posisi: Math.max(0, units.findIndex((u) => u.id === awal)),
                kecil: false,
                tarik: null,
                get n() { return this.units.length; },
                get m() { return this.n * this.salinan; },
                get indeks() { return ((this.posisi % this.n) + this.n) % this.n; },
                get info() { return this.units[this.indeks] ?? {}; },
                // Jumlah kartu yang tampil di tiap sisi kartu aktif.
                get jangkau() { return this.n >= 5 ? 2 : (this.n >= 2 ? 1 : 0); },
                init() {
                    const mq = matchMedia('(min-width: 640px)');
                    this.kecil = ! mq.matches;
                    mq.addEventListener('change', (e) => { this.kecil = ! e.matches; });
                    this.$watch('posisi', () => { this.aktif = this.info.id; });
                },
                offset(v) {
                    let o = (((v - this.posisi) % this.m) + this.m) % this.m;
                    return o > this.m / 2 ? o - this.m : o;
                },
                gaya(v) {
                    const o = this.offset(v);
                    const r = this.jangkau;
                    const lebarKartu = this.kecil ? 112 : 144;
                    const langkah = 22;                                    // derajat antar kartu
                    const rad = langkah * Math.PI / 180;
                    const jari = (lebarKartu + (this.kecil ? 14 : 22)) / Math.sin(rad);
                    const oc = Math.max(-(r + 1), Math.min(r + 1, o));    // kartu tersembunyi menunggu di tepi
                    const x = jari * Math.sin(oc * rad);
                    const z = jari * (1 - Math.cos(oc * rad));
                    const jarak = Math.abs(o);
                    return {
                        transform: `translate(-50%, -50%) translate3d(${x.toFixed(1)}px, 0, ${z.toFixed(1)}px) rotateY(${-oc * langkah}deg)`,
                        opacity: jarak > r ? 0 : 1 - jarak * 0.14,
                        zIndex: 50 - jarak,
                        pointerEvents: jarak > r ? 'none' : 'auto',
                    };
                },
                geser(arah, fokus = false) {
                    if (this.n < 2) return;
                    this.posisi += arah;
                    if (fokus) this.$nextTick(() => document.getElementById('tab-' + this.aktif)?.focus({ preventScroll: true }));
                },
                // Pilih unit lewat titik indikator: putar ke arah terdekat.
                pilih(id) {
                    let d = (((this.units.findIndex((u) => u.id === id) - this.indeks) % this.n) + this.n) % this.n;
                    if (d > this.n / 2) d -= this.n;
                    this.posisi += d;
                },
                klikKartu(v) {
                    if (this.tarik?.bergeser) return;   // klik sisa dari gerakan geser, abaikan
                    this.posisi += this.offset(v);
                },
                mulaiTarik(e) { this.tarik = { x: e.clientX, bergeser: false }; },
                gerakTarik(e) {
                    if (! this.tarik) return;
                    const dx = e.clientX - this.tarik.x;
                    if (Math.abs(dx) > 45) {
                        this.geser(dx < 0 ? 1 : -1);
                        this.tarik.x = e.clientX;
                        this.tarik.bergeser = true;
                    }
                },
                selesaiTarik() { setTimeout(() => { this.tarik = null; }); },
            }));

            @auth
                @if ($errors->any() && old('_modal'))
                    // Validasi gagal: buka lagi modal yang sama dengan isian sebelumnya.
                    const lama = @js(collect(old())->except(['_token', '_method'])->all());
                    lama.id = lama._id ?? '';
                    // Nilai checkbox dari old() berupa teks "1"/"0"; ubah ke boolean agar x-model menampilkan centang yang benar.
                    if ('tampil_hero' in lama) lama.tampil_hero = String(lama.tampil_hero) === '1';
                    Alpine.store('modal').open(lama._modal, lama);
                @endif
            @endauth
        });
    </script>
</head>

<body x-data @class(['pb-24' => auth()->check(), 'tanpa-intro' => session('status') || $errors->any()])>

    {{-- ================= NAVBAR ================= --}}
    <header x-data="navAktif(@js(array_keys($menu)))" @keydown.escape.window="buka = false" class="sticky top-3 z-50 px-3 sm:top-4">
        <nav class="mx-auto flex max-w-5xl items-center gap-2 rounded-full border border-garis/80 bg-white/85 p-1.5 pl-2.5 shadow-[0_12px_32px_-20px_rgba(20,35,26,.4)] backdrop-blur-md sm:pl-3" aria-label="Utama">
            <a href="#beranda" @click="pilih(null)" class="flex min-w-0 items-center gap-2.5">
                <img src="{{ asset('images/logo/perkom.png') }}" alt="" class="h-9 w-9 shrink-0 object-contain">
                <span class="font-display text-[13px] font-semibold leading-[1.15] tracking-[-0.02em] sm:text-[14px]">
                    <span class="block">Perekonomian Pondok</span>
                    <span class="block">Pesantren Abdussalam</span>
                </span>
            </a>

            <div class="ml-auto hidden items-center gap-0.5 lg:flex">
                @foreach ($menu as $href => $label)
                    <a href="{{ $href }}" @click="pilih(@js($href))"
                       :aria-current="aktif === @js($href) ? 'true' : null"
                       class="rounded-full px-3 py-2 text-[15px] font-medium transition-[color,background-color,box-shadow] duration-300"
                       :class="aktif === @js($href)
                           ? 'bg-hijau-muda text-hijau-tua shadow-[inset_0_0_0_1px_rgba(47,138,52,.18)]'
                           : 'text-tinta/75 hover:bg-kanvas hover:text-tinta'">{{ $label }}</a>
                @endforeach
            </div>

            @if ($waUtama)
                <a href="{{ $waUtama }}" target="_blank" rel="noopener" class="btn-gelap ml-2 hidden shrink-0 py-2 lg:inline-flex">Chat WhatsApp</a>
            @endif

            <button type="button" class="ml-auto grid h-10 w-10 shrink-0 place-items-center rounded-full hover:bg-kanvas lg:hidden" @click="buka = !buka" :aria-expanded="buka" aria-controls="menu-seluler" aria-label="Menu">
                <x-ikon name="menu" x-show="!buka" />
                <x-ikon name="tutup" x-show="buka" x-cloak />
            </button>
        </nav>

        <div id="menu-seluler" x-show="buka" x-cloak x-transition.opacity @click.outside="buka = false" class="mx-auto mt-2 max-w-5xl rounded-3xl border border-garis bg-white p-2 shadow-xl lg:hidden">
            @foreach ($menu as $href => $label)
                <a href="{{ $href }}" @click="pilih(@js($href))"
                   :aria-current="aktif === @js($href) ? 'true' : null"
                   class="block rounded-2xl px-4 py-3 font-medium transition-colors"
                   :class="aktif === @js($href) ? 'bg-hijau-muda text-hijau-tua' : 'hover:bg-kanvas'">{{ $label }}</a>
            @endforeach
            @if ($waUtama)
                <a href="{{ $waUtama }}" target="_blank" rel="noopener" class="btn-gelap mt-1 w-full py-3">Chat WhatsApp</a>
            @endif
        </div>
    </header>

    {{-- Notifikasi hasil aksi admin --}}
    @if (session('status'))
        <div x-data="{ tampil: true }" x-init="setTimeout(() => tampil = false, 4000)" x-show="tampil" x-transition.opacity.duration.300ms
             class="pointer-events-none fixed inset-x-0 top-20 z-[80] flex justify-center px-4" role="status">
            <p class="pointer-events-auto flex items-center gap-2 rounded-full bg-tinta px-5 py-2.5 text-sm font-medium text-white shadow-lg">
                <x-ikon name="centang" class="h-4 w-4 text-emas" /> {{ session('status') }}
            </p>
        </div>
    @endif

    <main>
        {{-- ================= HERO ================= --}}
        <section id="beranda" @class([
            'overflow-hidden px-4 pb-24 pt-10 sm:pb-32 sm:pt-14' => ! $adaLatar,
            'px-3 pb-24 pt-5 sm:px-4 sm:pb-32 sm:pt-6' => $adaLatar,
        ])>
          <div @class([
              'panel-hero relative isolate mx-auto max-w-[88rem] overflow-hidden rounded-[28px] px-4 pb-14 pt-10 shadow-[0_40px_80px_-40px_rgba(20,35,26,.45)] sm:rounded-[40px] sm:pb-20 sm:pt-14' => $adaLatar,
              'hero-terang' => $adaLatar && $latar['teks'] === 'terang',
          ])
          @auth
              x-data="aturLogo(
                  @js($idHero),
                  @js($unitHero->sortBy(fn ($u) => [$u->parent_id !== null, $u->id])->pluck('id')->map(fn ($id) => (string) $id)->values()),
                  @js($slotHero),
                  @js($unitHero->mapWithKeys(fn ($u) => [(string) $u->id => (bool) $u->logo_lebar])),
                  @js($ukuranUbin)
              )"
              @pointermove.window="gerak($event)" @pointerup.window="lepas()" @pointercancel.window="lepas()"
              @keydown.escape.window="aktif && batal()"
          @endauth>
            @if ($adaLatar)
                {{-- Background hero dari admin: gradasi, foto, atau video (file / link / YouTube) --}}
                <div class="absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
                    @if ($latar['tipe'] === 'gradasi')
                        <div class="absolute inset-0" style="background-image: linear-gradient({{ $latar['arah'] }}deg, {{ $latar['warna1'] }}, {{ $latar['warna2'] }})"></div>
                    @elseif ($latar['tipe'] === 'foto')
                        <img src="{{ $latar['url'] }}" alt="" class="absolute inset-0 h-full w-full object-cover">
                    @elseif ($latar['youtube'])
                        {{-- YouTube tidak punya mode "cover": ukuran iframe dihitung agar selalu menutup panel --}}
                        <div class="absolute inset-0 bg-tinta" x-data="{ w: 0, h: 0 }"
                             x-init="const ukur = () => { const r = $el.getBoundingClientRect(); const s = Math.max(r.width / 16, r.height / 9) * 1.18; w = Math.ceil(s * 16); h = Math.ceil(s * 9); }; ukur(); new ResizeObserver(ukur).observe($el)">
                            <iframe class="pointer-events-none absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 border-0"
                                    :style="`width: ${w}px; height: ${h}px`" tabindex="-1" title="Video latar"
                                    src="https://www.youtube-nocookie.com/embed/{{ $latar['youtube'] }}?autoplay=1&mute=1&loop=1&playlist={{ $latar['youtube'] }}&controls=0&disablekb=1&modestbranding=1&playsinline=1&rel=0&iv_load_policy=3"
                                    allow="autoplay; encrypted-media; picture-in-picture"></iframe>
                        </div>
                    @else
                        <video src="{{ $latar['url'] }}" class="absolute inset-0 h-full w-full object-cover" autoplay muted loop playsinline preload="auto"
                               x-data x-init="if (matchMedia('(prefers-reduced-motion: reduce)').matches) $el.pause()"></video>
                    @endif

                    @if (in_array($latar['tipe'], ['foto', 'video'], true) && $latar['gelap'] > 0)
                        <div class="absolute inset-0 bg-black" style="opacity: {{ $latar['gelap'] / 100 }}"></div>
                    @endif
                </div>
            @endif

            @auth
                {{-- Bar mode atur posisi logo (desktop) --}}
                <div x-show="aktif" x-cloak x-transition.opacity class="relative z-40 mx-auto mb-4 hidden w-fit md:block">
                    <form method="POST" action="{{ route('admin.logo-hero.update') }}"
                          class="flex flex-wrap items-center justify-center gap-2 rounded-full bg-tinta p-1.5 pl-4 text-sm text-white shadow-[0_20px_40px_-12px_rgba(20,35,26,.6)]"
                          x-data="{ kirim: false }" @submit="kirim = true">
                        @csrf
                        @method('PUT')
                        <template x-for="k in urutan" :key="k"><input type="hidden" name="urutan[]" :value="k"></template>
                        <span class="flex items-center gap-2 font-medium">
                            <span class="h-2 w-2 rounded-full bg-emas"></span>
                            <span x-text="pilih ? 'Klik logo lain untuk menukar' : 'Seret logo ke posisi lain, atau klik dua logo untuk menukar'"></span>
                        </span>
                        <button type="button" @click="bawaan()" class="rounded-full px-3 py-1.5 hover:bg-white/10">Urutan awal</button>
                        <button type="button" @click="batal()" class="rounded-full bg-white/10 px-3 py-1.5 hover:bg-white/20">Batal</button>
                        <button type="submit" :disabled="kirim || ! berubah" class="rounded-full bg-emas px-4 py-1.5 font-semibold text-tinta transition hover:brightness-95 disabled:opacity-50">
                            <span x-text="kirim ? 'Menyimpan...' : 'Simpan posisi'"></span>
                        </button>
                    </form>
                </div>
            @endauth

            {{-- Desktop: pusat pesantren terhubung ke setiap unit usaha --}}
            <div class="relative mx-auto hidden aspect-[1000/340] max-w-5xl md:block" aria-hidden="true" @auth x-ref="panggungLogo" @endauth>
                {{-- Garis penghubung dihitung dari jumlah logo (App\Support\TataLetakHero) --}}
                <svg class="hero-garis absolute inset-0 h-full w-full" viewBox="0 0 1000 340" fill="none">
                    <g stroke="#CFD5DB" stroke-width="1.25" stroke-linejoin="round">
                        @if ($tataHero['utama'])
                            <path pathLength="1" d="{{ $tataHero['utama'] }}" />
                        @endif
                        @foreach ($tataHero['cabang'] as $garis)
                            <path pathLength="1" d="{{ $garis }}" />
                        @endforeach
                    </g>
                    <g fill="#2F8A34">
                        @foreach ($tataHero['titik'] as [$cx, $cy])
                            <circle cx="{{ $cx }}" cy="{{ $cy }}" r="3.5" />
                        @endforeach
                    </g>
                </svg>

                <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <div class="hero-ubin ubin-pusat h-32 w-32 p-4 lg:h-40 lg:w-40 lg:p-5">
                        <img src="{{ asset('images/logo/perkom.png') }}" alt="" class="h-full w-full object-contain">
                    </div>
                </div>

                @foreach ($unitHero as $posisi => $unitLogo)
                    @php $slot = $slotHero[$posisi]; $kunci = (string) $unitLogo->id; @endphp
                    <div class="absolute -translate-x-1/2 -translate-y-1/2" style="left: {{ $slot['x'] }}%; top: {{ $slot['y'] }}%;"
                         @auth :style="gayaWadah('{{ $kunci }}')" :class="kelasWadah('{{ $kunci }}')" @pointerdown="mulai($event, '{{ $kunci }}')" @endauth>
                        <div style="--d: {{ $slot['d'] }}s"
                             @auth
                                 {{-- Ukuran ikut posisi saat ditukar, jadi kelas ukuran hanya lewat binding (tidak digabung dengan kelas statis). --}}
                                 class="hero-ubin ubin transition-[width,height,padding,box-shadow] duration-500"
                                 :class="kelasUbin('{{ $kunci }}')"
                             @else
                                 class="hero-ubin ubin {{ $ukuranUbin[$jenisUbin($unitLogo, $posisi)] }}"
                             @endauth>
                            <img src="{{ $unitLogo->logo_url }}" alt="" draggable="false" class="pointer-events-none h-full w-full object-contain">
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Seluler: versi ringkas tanpa garis, mengikuti urutan logo yang sama --}}
            <div class="md:hidden" aria-hidden="true">
                <div class="hero-ubin ubin-pusat mx-auto h-28 w-28 p-3.5">
                    <img src="{{ asset('images/logo/perkom.png') }}" alt="" class="h-full w-full object-contain">
                </div>
                <div class="mx-auto mt-8 grid max-w-xs grid-cols-3 gap-3">
                    @foreach ($unitHero as $posisi => $unitLogo)
                        <div class="hero-ubin ubin aspect-square p-3" style="--d: {{ $slotHero[$posisi]['d'] }}s">
                            <img src="{{ $unitLogo->logo_url }}" alt="" class="h-full w-full object-contain">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="mx-auto mt-12 max-w-3xl text-center md:mt-8">
                <h1 class="judul-besar blur-masuk text-[44px] sm:text-6xl lg:text-[84px]" style="--d: .35s">{{ $heroJudul }}</h1>
                @if ($heroDeskripsi)
                    <p class="hero-deskripsi blur-masuk mx-auto mt-6 max-w-xl text-lg leading-relaxed text-redup sm:text-xl" style="--d: .5s">{{ $heroDeskripsi }}</p>
                @endif
                <div class="blur-masuk mt-9 flex flex-wrap justify-center gap-3" style="--d: .62s">
                    <a href="#unit" class="btn-hijau px-6 py-3">Lihat unit usaha</a>
                    <a href="#cabang" class="btn-putih px-6 py-3">Cari cabang toserba</a>
                </div>

                @auth
                    <div class="mt-8 flex flex-wrap justify-center gap-2">
                        <button type="button" class="btn-admin"
                            @click="$store.modal.open('hero', @js([
                                'hero_judul' => $heroJudul,
                                'hero_deskripsi' => $heroDeskripsi,
                                'whatsapp' => $pengaturan['whatsapp'] ?? '',
                            ]))">
                            <x-ikon name="pensil" class="h-4 w-4" /> Edit teks pembuka
                        </button>
                        <button type="button" class="btn-admin"
                            @click="$store.modal.open('latar', @js([
                                'tipe' => $latar['tipe'],
                                'warna1' => $latar['warna1'],
                                'warna2' => $latar['warna2'],
                                'arah' => $latar['arah'],
                                'sumber' => $latar['sumber'],
                                'link' => $latar['link'] ?? '',
                                'gelap' => $latar['gelap'],
                                'teks' => $latar['teks'],
                                'file_url' => $latar['sumber'] === 'file' ? $latar['url'] : null,
                                'file_tipe' => $latar['sumber'] === 'file' && $latar['path'] ? $latar['tipe'] : null,
                            ]))">
                            <x-ikon name="foto" class="h-4 w-4" /> Ubah background
                        </button>
                        @if ($unitHero->count() > 1)
                        <button type="button" class="btn-admin hidden md:inline-flex" x-show="! aktif" @click="mulaiAtur()">
                            <x-ikon name="geser" class="h-4 w-4" /> Atur posisi logo
                        </button>
                        @endif
                    </div>
                @endauth
            </div>
          </div>
        </section>

        {{-- ================= VISI & MISI ================= --}}
        <section id="visi-misi" class="px-4 pb-24 sm:pb-32">
            <div class="mx-auto max-w-6xl">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="ubin-hijau mx-auto mb-6 h-14 w-14 text-white"><x-ikon name="kompas" class="h-7 w-7" /></span>
                    <h2 class="judul-section">Visi &amp; misi</h2>
                    <p class="mx-auto mt-4 max-w-lg text-lg text-redup">Arah yang kami pegang dalam mengelola setiap unit usaha pesantren.</p>

                    @auth
                        <button type="button" class="btn-admin mt-6"
                            @click="$store.modal.open('visi', @js(['visi' => $visi, 'misi' => $pengaturan['misi'] ?? '']))">
                            <x-ikon name="pensil" class="h-4 w-4" /> Edit visi &amp; misi
                        </button>
                    @endauth
                </div>

                <div class="mt-14 grid gap-4 lg:grid-cols-12">
                    <article class="kartu flex flex-col p-8 sm:p-10 lg:col-span-5">
                        <h3 class="text-[15px] font-semibold text-hijau">Visi</h3>
                        <p class="mt-6 font-display text-[26px] font-medium leading-[1.25] tracking-[-0.02em] sm:text-[32px]">
                            {{ $visi ?: 'Visi belum diisi.' }}
                        </p>
                    </article>

                    <article class="kartu p-8 sm:p-10 lg:col-span-7">
                        <h3 class="text-[15px] font-semibold text-hijau">Misi</h3>
                        @if ($misi->isEmpty())
                            <p class="mt-6 text-lg text-redup">Misi belum diisi.</p>
                        @else
                            <ul class="mt-4 divide-y divide-garis">
                                @foreach ($misi as $poin)
                                    <li class="flex gap-4 py-4 last:pb-0">
                                        <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-full bg-hijau-muda text-hijau">
                                            <x-ikon name="centang" class="h-4 w-4" />
                                        </span>
                                        <span class="text-lg leading-relaxed">{{ $poin }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </article>
                </div>
            </div>
        </section>

        {{-- ================= UNIT USAHA (CAROUSEL 3D) ================= --}}
        <section id="unit" class="px-4 pb-24 sm:pb-32">
            <div class="kartu mx-auto max-w-6xl overflow-hidden px-5 py-14 sm:px-10 sm:py-20"
                 x-data="karuselUnit(@js($dataUnit), @js($tabAwal), @js($salinanUnit))">

                <div class="mx-auto max-w-2xl text-center">
                    <span class="ubin-hijau mx-auto mb-6 h-14 w-14 text-white"><x-ikon name="piring" class="h-7 w-7" /></span>
                    <h2 class="judul-section">Unit usaha</h2>
                    <p class="mx-auto mt-4 max-w-lg text-lg text-redup">Pilih unit untuk melihat menu dan produknya.</p>

                    @auth
                        <button type="button" class="btn-admin mt-6" @click="$store.modal.open('unit')">
                            <x-ikon name="tambah" class="h-4 w-4" /> Tambah unit
                        </button>
                    @endauth
                </div>

                @if ($units->isEmpty())
                    <div class="mx-auto mt-12 max-w-md rounded-3xl border border-dashed border-garis px-6 py-12 text-center text-redup">
                        Unit usaha belum ditambahkan.
                    </div>
                @else
                    {{-- Panggung: kartu logo di dinding silinder, angka unit aktif samar di belakang --}}
                    <div class="relative -mx-5 mt-10 sm:-mx-10">
                        <div class="pointer-events-none absolute inset-0 grid select-none place-items-center overflow-hidden" aria-hidden="true">
                            <template x-for="u in [info]" :key="u.id">
                                <span class="keterangan-masuk font-display text-[150px] font-semibold leading-none tracking-rapat text-tinta/[.045] tabular-nums sm:text-[220px]"
                                      x-text="String(indeks + 1).padStart(2, '0')"></span>
                            </template>
                        </div>
                        <div class="pointer-events-none absolute bottom-3 left-1/2 h-10 w-2/3 max-w-lg -translate-x-1/2 rounded-[50%] bg-tinta/10 blur-2xl" aria-hidden="true"></div>

                        <div role="tablist" aria-label="Unit usaha"
                             class="panggung-unit relative h-52 cursor-grab select-none active:cursor-grabbing sm:h-64"
                             @pointerdown="mulaiTarik($event)"
                             @pointermove="gerakTarik($event)"
                             @pointerup="selesaiTarik()" @pointercancel="selesaiTarik()" @pointerleave="selesaiTarik()">
                            @for ($salinan = 0; $salinan < $salinanUnit; $salinan++)
                                @foreach ($units as $unit)
                                    @php $v = $salinan * $units->count() + $loop->index; @endphp
                                    <button type="button" x-cloak
                                        @if ($salinan === 0)
                                            role="tab" id="tab-{{ $unit->id }}" aria-controls="panel-{{ $unit->id }}"
                                            :aria-selected="aktif === {{ $unit->id }}"
                                            :tabindex="aktif === {{ $unit->id }} ? 0 : -1"
                                            @keydown.arrow-right.prevent="geser(1, true)"
                                            @keydown.arrow-left.prevent="geser(-1, true)"
                                        @else
                                            aria-hidden="true" tabindex="-1"
                                        @endif
                                        @click="klikKartu({{ $v }})"
                                        :class="offset({{ $v }}) === 0 && 'aktif'"
                                        :style="gaya({{ $v }})"
                                        class="kartu-unit absolute left-1/2 top-1/2 h-36 w-28 rounded-[22px] sm:h-44 sm:w-36">
                                        <span class="isi flex h-full w-full flex-col rounded-[22px] border border-garis p-3 sm:p-3.5">
                                            <span class="text-left text-[11px] font-semibold tabular-nums text-redup">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                            <span class="grid min-h-0 flex-1 place-items-center">
                                                @if ($unit->logo_url)
                                                    <img src="{{ $unit->logo_url }}" alt="" draggable="false"
                                                         @class(['object-contain', 'h-14 w-14 sm:h-[4.5rem] sm:w-[4.5rem]' => ! $unit->logo_lebar, 'h-10 w-full' => $unit->logo_lebar])>
                                                @else
                                                    <span class="ubin-hijau h-12 w-12 font-display text-xl font-semibold text-white sm:h-14 sm:w-14">{{ mb_substr($unit->nama_unit, 0, 1) }}</span>
                                                @endif
                                            </span>
                                            <span class="line-clamp-2 min-h-[2lh] text-center text-[12px] font-semibold leading-tight sm:text-[13px]">{{ $unit->nama_unit }}</span>
                                        </span>
                                    </button>
                                @endforeach
                            @endfor
                        </div>
                    </div>

                    {{-- Keterangan unit aktif, penghitung, titik indikator, dan tombol panah --}}
                    <div class="mt-6 grid grid-cols-2 items-center gap-y-5 sm:grid-cols-[1fr_auto_1fr] sm:gap-x-6">
                        <div class="col-span-2 text-center sm:order-2 sm:col-span-1">
                            <p class="text-[11px] font-semibold uppercase tracking-[.22em] text-redup/80">Geser &middot; Ketuk &middot; Tombol panah</p>
                            <div class="mt-3 min-h-[4.5rem]" aria-live="polite">
                                <template x-for="u in [info]" :key="u.id">
                                    <div class="keterangan-masuk">
                                        <h3 class="font-display text-2xl font-semibold tracking-rapat sm:text-[28px]" x-text="u.nama"></h3>
                                        <p class="mx-auto mt-1 line-clamp-2 max-w-md text-[15px] leading-relaxed text-redup"
                                           x-text="u.deskripsi || (u.produk + ' produk')"></p>
                                    </div>
                                </template>
                            </div>
                            <div class="mt-3 flex justify-center gap-1.5">
                                <template x-for="(u, i) in units" :key="u.id">
                                    <button type="button" @click="pilih(u.id)" :aria-label="'Tampilkan ' + u.nama"
                                            class="h-1.5 rounded-full transition-all duration-500"
                                            :class="i === indeks ? 'w-5 bg-hijau' : 'w-1.5 bg-garis hover:bg-redup/50'"></button>
                                </template>
                            </div>
                        </div>

                        <p class="font-display sm:order-1" aria-hidden="true">
                            <span class="text-3xl font-semibold tabular-nums tracking-rapat" x-text="String(indeks + 1).padStart(2, '0')"></span>
                            <span class="text-sm text-redup">/ {{ str_pad($units->count(), 2, '0', STR_PAD_LEFT) }}</span>
                        </p>

                        <div class="flex justify-end gap-2 sm:order-3">
                            <button type="button" @click="geser(-1)" class="grid h-10 w-10 place-items-center rounded-full border border-garis bg-white text-tinta shadow-sm transition hover:border-tinta/30 active:scale-95" aria-label="Unit sebelumnya">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                            </button>
                            <button type="button" @click="geser(1)" class="grid h-10 w-10 place-items-center rounded-full border border-garis bg-white text-tinta shadow-sm transition hover:border-tinta/30 active:scale-95" aria-label="Unit berikutnya">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                            </button>
                        </div>
                    </div>

                    @foreach ($units as $unit)
                        <div role="tabpanel" id="panel-{{ $unit->id }}" aria-labelledby="tab-{{ $unit->id }}" tabindex="0"
                             x-show="aktif === {{ $unit->id }}"
                             {{-- Hanya transisi masuk: panel lama langsung hilang, jadi dua panel tidak tampil bertumpuk dan halaman tidak melompat. --}}
                             x-transition:enter="transition duration-300 ease-out"
                             x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             @if ((int) $unit->id !== $tabAwal) x-cloak @endif
                             class="mt-10 border-t border-garis pt-4">

                            {{-- Nama & deskripsi unit sudah tampil di bawah carousel; di sini tinggal kontrol admin. --}}
                            @auth
                                <div class="flex justify-center pt-4">
                                    <div class="flex flex-wrap justify-center gap-2">
                                        <button type="button" class="btn-admin" @click="$store.modal.open('produk', { unit_id: '{{ $unit->id }}' })">
                                            <x-ikon name="tambah" class="h-4 w-4" /> Produk
                                        </button>
                                        <button type="button" class="btn-admin" @click="$store.modal.open('unit', { parent_id: '{{ $unit->id }}' })">
                                            <x-ikon name="tambah" class="h-4 w-4" /> Sub-unit
                                        </button>
                                        <button type="button" class="btn-admin"
                                            @click="$store.modal.open('unit', @js([
                                                'id' => (string) $unit->id,
                                                'nama_unit' => $unit->nama_unit,
                                                'deskripsi' => $unit->deskripsi,
                                                'parent_id' => '',
                                                'logo_url' => $unit->logo_url,
                                                'logo_upload' => (bool) $unit->logo_path,
                                                'tampil_hero' => $unit->tampil_hero,
                                            ]))">
                                            <x-ikon name="pensil" class="h-4 w-4" /> Edit
                                        </button>
                                        <button type="button" class="btn-admin-hapus"
                                            @click="$store.modal.open('hapus', @js([
                                                'jenis' => 'unit',
                                                'label' => $unit->nama_unit,
                                                'action' => route('admin.unit.destroy', $unit),
                                                'catatan' => 'Semua sub-unit dan produk di dalamnya ikut terhapus.',
                                            ]))">
                                            <x-ikon name="hapus" class="h-4 w-4" /> Hapus
                                        </button>
                                    </div>
                                </div>
                            @endauth

                            <x-produk-grid :produks="$unit->produks" :logo="$unit->logo_url" :logo-lebar="$unit->logo_lebar" />

                            @foreach ($unit->children as $sub)
                                <div class="mt-14">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                                        <div>
                                            <h4 class="font-display text-2xl font-semibold tracking-rapat">{{ $sub->nama_unit }}</h4>
                                            @if ($sub->deskripsi)
                                                <p class="mt-1 max-w-xl text-redup">{{ $sub->deskripsi }}</p>
                                            @endif
                                        </div>

                                        @auth
                                            <div class="flex flex-wrap gap-2">
                                                <button type="button" class="btn-admin" @click="$store.modal.open('produk', { unit_id: '{{ $sub->id }}' })">
                                                    <x-ikon name="tambah" class="h-4 w-4" /> Produk
                                                </button>
                                                <button type="button" class="btn-admin"
                                                    @click="$store.modal.open('unit', @js([
                                                        'id' => (string) $sub->id,
                                                        'nama_unit' => $sub->nama_unit,
                                                        'deskripsi' => $sub->deskripsi,
                                                        'parent_id' => (string) $sub->parent_id,
                                                        'logo_url' => $sub->logo_url,
                                                        'logo_upload' => (bool) $sub->logo_path,
                                                        'tampil_hero' => $sub->tampil_hero,
                                                    ]))">
                                                    <x-ikon name="pensil" class="h-4 w-4" /> Edit
                                                </button>
                                                <button type="button" class="btn-admin-hapus"
                                                    @click="$store.modal.open('hapus', @js([
                                                        'jenis' => 'sub-unit',
                                                        'label' => $sub->nama_unit,
                                                        'action' => route('admin.unit.destroy', $sub),
                                                        'catatan' => 'Semua produk di sub-unit ini ikut terhapus.',
                                                    ]))">
                                                    <x-ikon name="hapus" class="h-4 w-4" /> Hapus
                                                </button>
                                            </div>
                                        @endauth
                                    </div>

                                    <x-produk-grid :produks="$sub->produks" :logo="$unit->logo_url" :logo-lebar="$unit->logo_lebar" />
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif
            </div>
        </section>

        {{-- ================= CABANG TOSERBA ================= --}}
        <section id="cabang" class="px-4 pb-24 sm:pb-32">
            <div class="mx-auto max-w-6xl">
                <div class="mx-auto max-w-2xl text-center">
                    <img src="{{ asset('images/logo/toserba.png') }}" alt="Toserba Abdussalam" class="mx-auto mb-6 h-9 w-auto">
                    <h2 class="judul-section">{{ $cabangJudul }}</h2>
                    @if ($cabangDeskripsi)
                        <p class="mx-auto mt-4 max-w-lg text-lg text-redup">{{ $cabangDeskripsi }}</p>
                    @endif
                    @auth
                        <div class="mt-6 flex flex-wrap justify-center gap-2">
                            <button type="button" class="btn-admin"
                                @click="$store.modal.open('teksCabang', @js(['cabang_judul' => $cabangJudul, 'cabang_deskripsi' => $cabangDeskripsi]))">
                                <x-ikon name="pensil" class="h-4 w-4" /> Edit judul
                            </button>
                            <button type="button" class="btn-admin" @click="$store.modal.open('cabang')">
                                <x-ikon name="tambah" class="h-4 w-4" /> Tambah cabang
                            </button>
                        </div>
                    @endauth
                </div>

                @if ($cabangs->isEmpty())
                    <div class="mt-12 rounded-3xl border border-dashed border-garis bg-white/50 px-6 py-12 text-center text-redup">
                        Belum ada cabang yang ditambahkan.
                    </div>
                @else
                    {{-- Flex + justify-center: jika cabang kurang dari satu baris, kartunya tetap di tengah --}}
                    <div class="mt-12 flex flex-wrap justify-center gap-4">
                        @foreach ($cabangs as $cabang)
                            <article class="kartu group flex w-full flex-col overflow-hidden md:w-[calc((100%-1rem)/2)] lg:w-[calc((100%-2rem)/3)]">
                                <div class="relative aspect-[4/3] overflow-hidden bg-kanvas">
                                    @if ($cabang->foto_url)
                                        <img src="{{ $cabang->foto_url }}" alt="Foto {{ $cabang->nama_cabang }}" loading="lazy"
                                             class="h-full w-full object-cover transition duration-500 ease-out group-hover:scale-[1.04]">
                                    @else
                                        {{-- Belum ada foto (media lama berupa video / YouTube / embed peta juga tidak ditampilkan). --}}
                                        <div class="flex h-full flex-col items-center justify-center gap-3 bg-gradient-to-br from-hijau-muda to-kanvas text-hijau">
                                            <img src="{{ asset('images/logo/toserba.png') }}" alt="" class="h-8 w-auto opacity-80">
                                            @auth
                                                <span class="rounded-full bg-white/80 px-3 py-1 text-xs font-semibold text-emas-tua">Foto cabang belum diunggah</span>
                                            @endauth
                                        </div>
                                    @endif

                                    @auth
                                        <div class="absolute right-3 top-3 flex gap-1.5">
                                            <button type="button" class="ikon-admin" aria-label="Edit {{ $cabang->nama_cabang }}"
                                                @click="$store.modal.open('cabang', @js([
                                                    'id' => (string) $cabang->id,
                                                    'nama_cabang' => $cabang->nama_cabang,
                                                    'alamat' => $cabang->alamat,
                                                    'no_whatsapp' => $cabang->no_whatsapp,
                                                    'link_maps' => $cabang->peta_url,
                                                    'foto_url' => $cabang->foto_url,
                                                ]))">
                                                <x-ikon name="pensil" class="h-4 w-4" />
                                            </button>
                                            <button type="button" class="ikon-admin-hapus" aria-label="Hapus {{ $cabang->nama_cabang }}"
                                                @click="$store.modal.open('hapus', @js([
                                                    'jenis' => 'cabang',
                                                    'label' => $cabang->nama_cabang,
                                                    'action' => route('admin.cabang.destroy', $cabang),
                                                ]))">
                                                <x-ikon name="hapus" class="h-4 w-4" />
                                            </button>
                                        </div>
                                    @endauth
                                </div>

                                <div class="flex flex-1 flex-col p-6">
                                    <h3 class="font-display text-xl font-semibold tracking-[-0.02em]">{{ $cabang->nama_cabang }}</h3>
                                    <p class="mt-2 flex gap-2 text-[15px] leading-relaxed text-redup">
                                        <x-ikon name="pin" class="mt-0.5 h-4 w-4 shrink-0" />
                                        <span>{{ $cabang->alamat }}</span>
                                    </p>
                                    <div class="mt-auto flex flex-wrap gap-2 pt-6">
                                        <a href="{{ $cabang->whatsapp_url }}" target="_blank" rel="noopener" class="btn-wa">
                                            <x-ikon name="chat" class="h-4 w-4" /> Chat WhatsApp
                                        </a>
                                        @if ($cabang->peta_url)
                                            <a href="{{ $cabang->peta_url }}" target="_blank" rel="noopener" class="btn-putih">
                                                <x-ikon name="peta" class="h-4 w-4" /> Buka peta
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        {{-- ================= BERITA ================= --}}
        @if ($tampilkanBerita)
        <section id="berita" class="px-4 pb-24 sm:pb-32">
            <div class="mx-auto max-w-6xl">
                <div class="mx-auto max-w-2xl text-center">
                    <span class="ubin-hijau mx-auto mb-6 h-14 w-14 text-white"><x-ikon name="koran" class="h-7 w-7" /></span>
                    <h2 class="judul-section">Berita &amp; kegiatan</h2>
                    <p class="mx-auto mt-4 max-w-lg text-lg text-redup">Kabar terbaru dari unit usaha dan kegiatan ekonomi pesantren.</p>
                    <div class="mt-6 flex flex-wrap items-center justify-center gap-2">
                        @auth
                            <button type="button" class="btn-admin" @click="$store.modal.open('berita')">
                                <x-ikon name="tambah" class="h-4 w-4" /> Tambah berita
                            </button>
                        @endauth
                        @if ($beritas->isNotEmpty())
                            <a href="{{ route('berita.index') }}" class="btn-putih">
                                Semua berita <x-ikon name="kanan" class="h-4 w-4" />
                            </a>
                        @endif
                    </div>
                </div>

                @if ($beritas->isEmpty())
                    <div class="mt-12 rounded-3xl border border-dashed border-garis bg-white/50 px-6 py-12 text-center text-redup">
                        Belum ada berita yang diterbitkan.
                    </div>
                @else
                    {{-- Bento: berita terbaru besar, dua lainnya mendatar di sampingnya --}}
                    <div class="mt-12 grid gap-4 lg:grid-cols-5">
                        <x-kartu-berita :berita="$beritas->first()" varian="besar"
                            class="{{ $beritas->count() > 1 ? 'lg:col-span-3' : 'lg:col-span-5' }}" />

                        @if ($beritas->count() > 1)
                            <div class="flex flex-col gap-4 lg:col-span-2">
                                @foreach ($beritas->skip(1) as $berita)
                                    <x-kartu-berita :berita="$berita" varian="mendatar" class="lg:flex-1" />
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

                @auth
                    @if ($beritaAdmin->isNotEmpty())
                        <div id="kelola-berita" class="mt-10 rounded-3xl border border-dashed border-emas bg-emas-muda/40 p-5">
                            <p class="text-sm font-semibold text-emas-tua">Kelola berita ({{ $beritaAdmin->count() }})</p>
                            <ul class="mt-3 divide-y divide-garis overflow-hidden rounded-2xl border border-garis bg-white">
                                @foreach ($beritaAdmin as $berita)
                                    <li class="flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3">
                                        <div class="min-w-0 flex-1">
                                            <a href="{{ route('berita.show', $berita) }}" class="block truncate font-semibold hover:text-hijau">{{ $berita->judul }}</a>
                                            <p class="text-xs text-redup">{{ $berita->terbit_pada->translatedFormat('j F Y') }}{{ $berita->foto_path ? '' : ' · tanpa foto' }}</p>
                                        </div>
                                        <div class="flex gap-1.5">
                                            <button type="button" class="ikon-admin" aria-label="Edit {{ $berita->judul }}"
                                                @click="$store.modal.open('berita', @js([
                                                    'id' => (string) $berita->id,
                                                    'judul' => $berita->judul,
                                                    'ringkasan' => $berita->ringkasan,
                                                    'isi' => $berita->isi,
                                                    'terbit_pada' => $berita->terbit_pada->toDateString(),
                                                    'foto_url' => $berita->foto_url,
                                                ]))">
                                                <x-ikon name="pensil" class="h-4 w-4" />
                                            </button>
                                            <button type="button" class="ikon-admin-hapus" aria-label="Hapus {{ $berita->judul }}"
                                                @click="$store.modal.open('hapus', @js([
                                                    'jenis' => 'berita',
                                                    'label' => $berita->judul,
                                                    'action' => route('admin.berita.destroy', $berita),
                                                ]))">
                                                <x-ikon name="hapus" class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endauth
            </div>
        </section>
        @endif

        {{-- ================= MITRA ================= --}}
        @if ($tampilkanMitra)
        <section id="mitra" class="pb-24 sm:pb-32">
            <div class="mx-auto max-w-2xl px-4 text-center">
                <span class="ubin-hijau mx-auto mb-6 h-14 w-14 text-white"><x-ikon name="jabat" class="h-7 w-7" /></span>
                <h2 class="judul-section">Bekerja sama dengan</h2>
                <p class="mx-auto mt-4 max-w-lg text-lg text-redup">Mitra yang ikut menggerakkan ekonomi pesantren.</p>
                @auth
                    <button type="button" class="btn-admin mt-6" @click="$store.modal.open('mitra')">
                        <x-ikon name="tambah" class="h-4 w-4" /> Tambah mitra
                    </button>
                @endauth
            </div>

            @if ($mitras->isNotEmpty())
                <div class="marquee mt-12" style="--durasi: {{ $mitras->count() * $ulangMitra * 3 }}s">
                    <div class="marquee-track">
                        @foreach ([false, true] as $duplikat)
                            <ul class="flex shrink-0" @if ($duplikat) aria-hidden="true" @endif>
                                @for ($i = 0; $i < $ulangMitra; $i++)
                                    @foreach ($mitras as $mitra)
                                        <li class="pr-4 sm:pr-5">
                                            <figure class="flex w-36 flex-col items-center gap-3 sm:w-40">
                                                <div class="ubin h-28 w-36 !rounded-3xl p-5 sm:w-40">
                                                    <img src="{{ $mitra->logo_url }}" alt="{{ $duplikat || $i > 0 ? '' : $mitra->nama_mitra }}" loading="lazy" class="max-h-full max-w-full object-contain">
                                                </div>
                                                <figcaption class="text-sm font-medium text-redup">{{ $mitra->nama_mitra }}</figcaption>
                                            </figure>
                                        </li>
                                    @endforeach
                                @endfor
                            </ul>
                        @endforeach
                    </div>
                </div>
            @else
                <p class="mt-10 text-center text-redup">Belum ada mitra yang ditambahkan.</p>
            @endif

            @auth
                @if ($mitras->isNotEmpty())
                    <div class="mx-auto mt-10 max-w-6xl px-4">
                        <div class="rounded-3xl border border-dashed border-emas bg-emas-muda/40 p-5">
                            <p class="text-sm font-semibold text-emas-tua">Kelola mitra</p>
                            <ul class="mt-3 flex flex-wrap gap-2">
                                @foreach ($mitras as $mitra)
                                    <li class="flex items-center gap-2 rounded-full border border-garis bg-white py-1 pl-2 pr-1">
                                        <img src="{{ $mitra->logo_url }}" alt="" class="h-6 w-6 object-contain">
                                        <span class="text-sm font-medium">{{ $mitra->nama_mitra }}</span>
                                        <button type="button" class="ikon-admin h-7 w-7" aria-label="Edit {{ $mitra->nama_mitra }}"
                                            @click="$store.modal.open('mitra', @js([
                                                'id' => (string) $mitra->id,
                                                'nama_mitra' => $mitra->nama_mitra,
                                                'logo_url' => $mitra->logo_url,
                                            ]))">
                                            <x-ikon name="pensil" class="h-3.5 w-3.5" />
                                        </button>
                                        <button type="button" class="ikon-admin-hapus h-7 w-7" aria-label="Hapus {{ $mitra->nama_mitra }}"
                                            @click="$store.modal.open('hapus', @js([
                                                'jenis' => 'mitra',
                                                'label' => $mitra->nama_mitra,
                                                'action' => route('admin.mitra.destroy', $mitra),
                                            ]))">
                                            <x-ikon name="hapus" class="h-3.5 w-3.5" />
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endif
            @endauth
        </section>
        @endif

        {{-- ================= TENTANG KAMI ================= --}}
        <section id="tentang-kami" class="px-4 pb-24 sm:pb-32">
            <div class="relative mx-auto flex max-w-6xl items-center justify-center lg:min-h-[38rem]">

                {{-- Desktop: foto kegiatan (atau logo unit) melayang di kiri & kanan --}}
                <div class="pointer-events-none absolute inset-0 hidden lg:block" aria-hidden="true">
                    @if ($fotoTentang->isNotEmpty())
                        @foreach ($fotoTentang->take(count($slotFoto)) as $foto)
                            @php [$posisi, $ukuran, $rotasi, $jeda] = $slotFoto[$loop->index]; @endphp
                            <div class="absolute" style="{{ $posisi }}; transform: rotate({{ $rotasi }}deg)">
                                <div class="foto-melayang {{ $ukuran }} overflow-hidden rounded-[22px] border-4 border-white bg-white shadow-[0_24px_48px_-20px_rgba(20,35,26,.45)]" style="--d: {{ $jeda }}s">
                                    <img src="{{ $foto->foto_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                </div>
                            </div>
                        @endforeach
                    @else
                        @foreach ($logoMelayang as $unit)
                            @php [$posisi, , $rotasi, $jeda] = $slotFoto[$loop->index]; @endphp
                            <div class="absolute" style="{{ $posisi }}; transform: rotate({{ $rotasi }}deg)">
                                <div @class(['foto-melayang ubin', 'h-24 w-24 p-3' => ! $unit->logo_lebar, 'h-16 w-40 !rounded-3xl px-4' => $unit->logo_lebar]) style="--d: {{ $jeda }}s">
                                    <img src="{{ $unit->logo_url }}" alt="" class="h-full w-full object-contain">
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <div class="relative z-10 mx-auto max-w-lg text-center lg:py-10">
                    {{-- Seluler & tablet: foto berjajar di atas teks --}}
                    @if ($fotoTentang->isNotEmpty())
                        <div class="mb-10 flex justify-center lg:hidden" aria-hidden="true">
                            @foreach ($fotoTentang->take(5) as $foto)
                                <div @class(['-mx-2 h-24 w-20 overflow-hidden rounded-2xl border-[3px] border-white bg-white shadow-[0_16px_30px_-16px_rgba(20,35,26,.5)] sm:h-28 sm:w-24', 'rotate-[-6deg]' => $loop->odd, 'rotate-[5deg] translate-y-3' => $loop->even])>
                                    <img src="{{ $foto->foto_url }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <span class="ubin-hijau mx-auto mb-6 h-14 w-14 text-white"><x-ikon name="masjid" class="h-7 w-7" /></span>
                    <p class="text-[12px] font-semibold uppercase tracking-[.22em] text-hijau">Tentang kami</p>
                    <h2 class="judul-section mt-3">{{ $tentangJudul }}</h2>
                    <p class="mx-auto mt-5 whitespace-pre-line text-lg leading-relaxed text-redup">{{ $tentangIsi }}</p>

                    {{-- Lebar kotak mengikuti jumlah angka (1-3) agar tidak ada kolom kosong --}}
                    @php $jumlahStat = max(1, min(3, $statistikTentang->count())); @endphp
                    <dl @class([
                            'mx-auto mt-10 grid divide-x divide-garis rounded-3xl border border-garis bg-white py-5 shadow-[0_18px_40px_-28px_rgba(20,35,26,.35)]',
                            'max-w-[12rem]' => $jumlahStat === 1,
                            'max-w-xs' => $jumlahStat === 2,
                            'max-w-md' => $jumlahStat === 3,
                        ])
                        style="grid-template-columns: repeat({{ $jumlahStat }}, minmax(0, 1fr))">
                        @foreach ($statistikTentang->take(3) as $stat)
                            <div class="flex flex-col-reverse items-center gap-1 px-2">
                                <dt class="text-[13px] leading-tight text-redup">{{ $stat['label'] }}</dt>
                                <dd class="font-display text-3xl font-semibold tracking-rapat">{{ $stat['angka'] }}</dd>
                            </div>
                        @endforeach
                    </dl>

                    @auth
                        <div class="mt-6 flex flex-wrap justify-center gap-2">
                            <button type="button" class="btn-admin"
                                @click="$store.modal.open('tentang', @js([
                                    'tentang_judul' => $tentangJudul,
                                    'tentang_isi' => $tentangIsi,
                                    'statistik' => $statistikIsian,
                                ]))">
                                <x-ikon name="pensil" class="h-4 w-4" /> Edit teks &amp; angka
                            </button>
                            <button type="button" class="btn-admin" @click="$store.modal.open('fotoTentang')">
                                <x-ikon name="foto" class="h-4 w-4" /> Kelola foto ({{ $fotoTentang->count() }}/{{ \App\Models\FotoTentang::MAKS }})
                            </button>
                        </div>
                    @endauth
                </div>
            </div>
        </section>

        {{-- ================= ULASAN ================= --}}
        <section id="ulasan" class="pb-24 sm:pb-32">
            <div class="mx-auto max-w-3xl px-4 text-center">
                <h2 class="judul-besar text-5xl sm:text-7xl">Kata pengunjung</h2>
                <p class="mx-auto mt-5 max-w-lg text-lg text-redup">
                    @if ($jumlahUlasan)
                        Rata-rata <span class="font-semibold text-tinta">{{ $rataRating }}</span> dari 5 bintang, berdasarkan {{ $jumlahUlasan }} ulasan.
                    @else
                        Belum ada ulasan. Ceritakan pengalaman Anda lewat form di bawah.
                    @endif
                </p>
            </div>

            @if ($ulasans->isNotEmpty())
                <div class="marquee mt-14 py-4" style="--durasi: {{ $ulasans->count() * $ulangUlasan * 9 }}s">
                    <div class="marquee-track">
                        @foreach ([false, true] as $duplikat)
                            <ul class="flex shrink-0 items-stretch" @if ($duplikat) aria-hidden="true" @endif>
                                @for ($i = 0; $i < $ulangUlasan; $i++)
                                    @foreach ($ulasans as $ulasan)
                                        <li class="flex pr-4 sm:pr-5">
                                            <article class="kartu flex w-[290px] flex-col p-6 sm:w-[340px]">
                                                <div class="flex items-center gap-3">
                                                    <span class="ubin-hijau h-11 w-11 shrink-0 font-display text-sm font-semibold text-white">{{ $ulasan->inisial }}</span>
                                                    <div class="min-w-0">
                                                        <p class="truncate font-semibold">{{ $ulasan->nama_pengunjung }}</p>
                                                        <p class="text-xs text-redup">{{ $ulasan->created_at->translatedFormat('j F Y') }}</p>
                                                    </div>
                                                </div>
                                                <div class="mt-5 flex items-center gap-1" aria-label="{{ $ulasan->rating }} dari 5 bintang">
                                                    @for ($b = 1; $b <= 5; $b++)
                                                        <x-ikon name="bintang" @class(['h-4 w-4', 'fill-emas text-emas' => $b <= $ulasan->rating, 'text-garis' => $b > $ulasan->rating]) />
                                                    @endfor
                                                    <span class="ml-1.5 text-sm font-semibold">{{ $ulasan->rating }},0</span>
                                                </div>
                                                <p class="mt-3 line-clamp-4 text-[15px] leading-relaxed text-tinta/80">“{{ $ulasan->komentar }}”</p>
                                            </article>
                                        </li>
                                    @endforeach
                                @endfor
                            </ul>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Form ulasan publik --}}
            <div class="mx-auto mt-14 max-w-xl px-4">
                <div class="kartu p-6 sm:p-8">
                    @if (session('ulasan_terkirim'))
                        <div class="mb-6 flex gap-3 rounded-2xl bg-hijau-muda p-4 text-[15px] text-hijau-tua" role="status">
                            <x-ikon name="centang" class="mt-0.5 h-5 w-5 shrink-0" />
                            <p>Terima kasih, ulasan Anda sudah terkirim dan akan tampil setelah ditinjau admin.</p>
                        </div>
                    @endif

                    <h3 class="font-display text-2xl font-semibold tracking-rapat">Tulis ulasan</h3>
                    <p class="mt-1 text-[15px] text-redup">Ulasan tampil di halaman ini setelah ditinjau admin.</p>

                    @error('ulasan')
                        <p class="mt-4 rounded-2xl bg-red-50 p-4 text-sm font-medium text-red-700" role="alert">{{ $message }}</p>
                    @enderror

                    <form method="POST" action="{{ route('ulasan.store') }}" class="mt-6 space-y-5"
                          x-data="{ rating: {{ (int) old('rating', 0) }}, sorot: 0, komentar: @js((string) old('komentar', '')), kirim: false }"
                          @submit="kirim = true">
                        @csrf

                        <div>
                            <label for="nama_pengunjung" class="label">Nama</label>
                            <input id="nama_pengunjung" name="nama_pengunjung" type="text" value="{{ old('nama_pengunjung') }}" required maxlength="100" autocomplete="name" class="field">
                            @error('nama_pengunjung') <p class="mt-1.5 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <fieldset>
                            <legend class="label">Rating</legend>
                            <div class="flex items-center gap-1" @mouseleave="sorot = 0">
                                @for ($b = 1; $b <= 5; $b++)
                                    <label class="cursor-pointer rounded-lg p-0.5 has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-hijau" @mouseenter="sorot = {{ $b }}">
                                        <input type="radio" name="rating" value="{{ $b }}" x-model.number="rating" class="sr-only" required>
                                        <span class="sr-only">{{ $b }} bintang</span>
                                        <x-ikon name="bintang" class="h-8 w-8 transition"
                                            ::class="(sorot || rating) >= {{ $b }} ? 'fill-emas text-emas' : 'text-garis'" />
                                    </label>
                                @endfor
                                <span class="ml-2 text-sm font-medium text-redup"
                                      x-text="['Pilih bintang', 'Buruk', 'Kurang', 'Cukup', 'Bagus', 'Sangat bagus'][sorot || rating]"></span>
                            </div>
                            @error('rating') <p class="mt-1.5 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                        </fieldset>

                        <div>
                            <label for="komentar" class="label">Ulasan</label>
                            <textarea id="komentar" name="komentar" rows="4" required minlength="10" maxlength="500" x-model="komentar" class="field resize-none"
                                      placeholder="Ceritakan pengalaman Anda: menu, pelayanan, atau tempatnya."></textarea>
                            <p class="mt-1.5 text-right text-xs text-redup" x-text="komentar.length + '/500'"></p>
                            @error('komentar') <p class="mt-1 text-sm font-medium text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="btn-hijau w-full py-3" :disabled="kirim">
                            <span x-show="!kirim">Kirim ulasan</span>
                            <span x-show="kirim" x-cloak>Mengirim...</span>
                        </button>
                    </form>
                </div>
            </div>

            {{-- Moderasi ulasan (admin) --}}
            @auth
                <div id="kelola-ulasan" class="mx-auto mt-10 max-w-3xl scroll-mt-28 px-4">
                    <div class="rounded-3xl border border-dashed border-emas bg-emas-muda/40 p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-4">
                            <h3 class="font-display text-xl font-semibold tracking-rapat">Kelola ulasan</h3>
                            <span class="rounded-full bg-emas px-3 py-1 text-xs font-bold text-tinta">{{ $jumlahPending }} menunggu</span>
                        </div>

                        @if ($ulasanAdmin->isEmpty())
                            <p class="mt-4 text-sm text-emas-tua">Belum ada ulasan masuk.</p>
                        @else
                            <ul class="mt-4 space-y-2">
                                @foreach ($ulasanAdmin as $ulasan)
                                    <li class="rounded-2xl border border-garis bg-white p-4">
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                            <p class="font-semibold">{{ $ulasan->nama_pengunjung }}</p>
                                            <p class="text-sm text-redup">{{ $ulasan->rating }}/5 bintang</p>
                                            @if ($ulasan->status === 'pending')
                                                <span class="rounded-full bg-emas-muda px-2 py-0.5 text-xs font-semibold text-emas-tua">Menunggu</span>
                                            @else
                                                <span class="rounded-full bg-hijau-muda px-2 py-0.5 text-xs font-semibold text-hijau-tua">Tampil</span>
                                            @endif
                                            <p class="text-xs text-redup sm:ml-auto">{{ $ulasan->created_at->diffForHumans() }}</p>
                                        </div>
                                        <p class="mt-2 text-[15px] leading-relaxed text-tinta/80">{{ $ulasan->komentar }}</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @if ($ulasan->status === 'pending')
                                                <form method="POST" action="{{ route('admin.ulasan.approve', $ulasan) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn-admin"><x-ikon name="centang" class="h-4 w-4" /> Setujui</button>
                                                </form>
                                            @endif
                                            <button type="button" class="btn-admin-hapus"
                                                @click="$store.modal.open('hapus', @js([
                                                    'jenis' => 'ulasan',
                                                    'label' => 'Ulasan dari ' . $ulasan->nama_pengunjung,
                                                    'action' => route('admin.ulasan.destroy', $ulasan),
                                                ]))">
                                                <x-ikon name="hapus" class="h-4 w-4" /> Hapus
                                            </button>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            @endauth
        </section>
    </main>

    {{-- ================= FOOTER ================= --}}
    <footer class="px-4 pb-10">
        <div class="kartu mx-auto flex max-w-6xl flex-col gap-8 p-8 sm:p-10 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-4">
                <img src="{{ asset('images/logo/perkom.png') }}" alt="" class="h-14 w-14 object-contain">
                <div>
                    <p class="font-display text-lg font-semibold tracking-[-0.02em]">Perekonomian Pondok Pesantren Abdussalam</p>
                    <p class="text-[15px] text-redup">Kubu Raya, Kalimantan Barat</p>
                </div>
            </div>
            <nav class="flex flex-wrap gap-x-6 gap-y-2 text-[15px]" aria-label="Footer">
                @foreach ($menu as $href => $label)
                    <a href="{{ $href }}" class="text-redup hover:text-tinta">{{ $label }}</a>
                @endforeach
            </nav>
        </div>
        {{-- Tidak ada tautan login untuk pengunjung; admin masuk lewat alamat /login. --}}
        <div class="mx-auto mt-6 max-w-6xl px-2 text-sm text-redup">
            <p>&copy; {{ now()->year }} Pondok Pesantren Abdussalam</p>
        </div>
    </footer>

    @auth
        {{-- ================= BAR MODE EDIT ================= --}}
        <div class="fixed inset-x-0 bottom-4 z-[60] flex justify-center px-3">
            <div class="flex items-center gap-1 rounded-full bg-tinta p-1.5 pl-4 text-sm text-white shadow-[0_20px_40px_-12px_rgba(20,35,26,.6)]">
                <span class="flex items-center gap-2 font-semibold">
                    <span class="h-2 w-2 rounded-full bg-emas"></span> Mode edit
                </span>
                <a href="#kelola-ulasan" class="ml-2 flex items-center gap-1.5 rounded-full px-3 py-1.5 hover:bg-white/10">
                    Ulasan
                    @if ($jumlahPending)
                        <span class="rounded-full bg-emas px-1.5 text-xs font-bold text-tinta">{{ $jumlahPending }}</span>
                    @endif
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="rounded-full bg-white/10 px-3 py-1.5 font-medium hover:bg-white/20">Keluar</button>
                </form>
            </div>
        </div>

        {{-- ================= MODAL ADMIN ================= --}}

        {{-- Teks pembuka --}}
        <x-modal name="hero" title="'Edit teks pembuka'">
            <form method="POST" action="{{ route('admin.pengaturan.update') }}" class="space-y-5"
                  x-data="{ kirim: false, get d() { return $store.modal.data } }" @submit="kirim = true">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="hero">

                <div>
                    <label for="hero_judul" class="label">Judul</label>
                    <input id="hero_judul" name="hero_judul" type="text" x-model="d.hero_judul" required maxlength="120" class="field">
                    <x-field-error for="hero_judul" modal="hero" />
                </div>
                <div>
                    <label for="hero_deskripsi" class="label">Deskripsi singkat</label>
                    <textarea id="hero_deskripsi" name="hero_deskripsi" rows="3" x-model="d.hero_deskripsi" maxlength="400" class="field"></textarea>
                    <x-field-error for="hero_deskripsi" modal="hero" />
                </div>
                <div>
                    <label for="whatsapp" class="label">Nomor WhatsApp utama</label>
                    <input id="whatsapp" name="whatsapp" type="tel" inputmode="tel" x-model="d.whatsapp" placeholder="081234567890" class="field">
                    <p class="bantuan">Dipakai tombol "Chat WhatsApp" di navigasi. Kosongkan untuk menyembunyikan tombol.</p>
                    <x-field-error for="whatsapp" modal="hero" />
                </div>

                <x-modal-aksi label="Simpan perubahan" />
            </form>
        </x-modal>

        {{-- Background hero --}}
        <x-modal name="latar" title="'Ubah background pembuka'" lebar="sm:max-w-2xl">
            <form method="POST" action="{{ route('admin.latar-hero.update') }}" enctype="multipart/form-data" class="space-y-6"
                  x-data="{
                      kirim: false,
                      pratinjau: null,
                      preset: [
                          { nama: 'Hijau pesantren', w1: '#2F8A34', w2: '#14231A', arah: 135, teks: 'terang' },
                          { nama: 'Hijau segar',     w1: '#8BDB5E', w2: '#256F29', arah: 160, teks: 'terang' },
                          { nama: 'Emas senja',      w1: '#F2B705', w2: '#B4541B', arah: 135, teks: 'terang' },
                          { nama: 'Malam',           w1: '#3B4A5A', w2: '#14231A', arah: 180, teks: 'terang' },
                          { nama: 'Mint lembut',     w1: '#E6F3E4', w2: '#FFFFFF', arah: 180, teks: 'gelap' },
                          { nama: 'Pagi cerah',      w1: '#FFF6D6', w2: '#E6F3E4', arah: 135, teks: 'gelap' },
                      ],
                      get d() { return $store.modal.data },
                      get media() { return ['foto', 'video'].includes(this.d.tipe) },
                      get youtubeId() {
                          const m = String(this.d.link || '').match(/(?:youtube\.com\/(?:watch\?v=|shorts\/|embed\/)|youtu\.be\/)([\w-]{11})/);
                          return m ? m[1] : null;
                      },
                      // Sumber gambar/video untuk pratinjau: file baru, file lama bertipe sama, atau link.
                      get src() {
                          if (this.d.sumber === 'link') return this.d.link || null;
                          return this.pratinjau || (this.d.file_tipe === this.d.tipe ? this.d.file_url : null);
                      },
                      pilihTipe(t) {
                          this.d.tipe = t;
                          this.pratinjau = null;
                          if (this.$refs.file) this.$refs.file.value = '';
                          if (t === 'foto' || t === 'video') this.d.teks = 'terang';
                      },
                      pakaiPreset(p) { this.d.warna1 = p.w1; this.d.warna2 = p.w2; this.d.arah = p.arah; this.d.teks = p.teks; },
                  }"
                  x-effect="$store.modal.name; pratinjau = null"
                  @submit="kirim = true">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="latar">

                {{-- Pratinjau langsung --}}
                <div class="relative isolate grid aspect-[16/8] place-items-center overflow-hidden rounded-3xl border border-garis bg-kanvas px-6 text-center" aria-hidden="true">
                    <div class="absolute inset-0 -z-10">
                        <template x-if="d.tipe === 'gradasi'">
                            <div class="absolute inset-0" :style="`background-image: linear-gradient(${d.arah}deg, ${d.warna1}, ${d.warna2})`"></div>
                        </template>
                        <template x-if="d.tipe === 'foto' && src">
                            <img :src="src" alt="" class="absolute inset-0 h-full w-full object-cover">
                        </template>
                        <template x-if="d.tipe === 'video' && d.sumber === 'link' && youtubeId">
                            <img :src="`https://i.ytimg.com/vi/${youtubeId}/hqdefault.jpg`" alt="" class="absolute inset-0 h-full w-full object-cover">
                        </template>
                        <template x-if="d.tipe === 'video' && src && !(d.sumber === 'link' && youtubeId)">
                            <video :src="src" class="absolute inset-0 h-full w-full object-cover" autoplay muted loop playsinline></video>
                        </template>
                        <div x-show="media && src" class="absolute inset-0 bg-black" :style="`opacity: ${d.gelap / 100}`"></div>
                    </div>

                    <div>
                        <p class="font-display text-3xl font-semibold leading-none tracking-rapat sm:text-[40px]"
                           :class="d.tipe !== 'bawaan' && d.teks === 'terang' ? 'text-white' : 'text-tinta'">Satu pesantren,<br>banyak usaha</p>
                        <p x-show="media && !src" class="mt-3 text-sm font-medium text-redup"
                           x-text="d.sumber === 'link' ? 'Tempel link untuk melihat pratinjau' : 'Pilih file untuk melihat pratinjau'"></p>
                    </div>
                </div>

                {{-- Jenis background --}}
                <fieldset>
                    <legend class="label">Jenis background</legend>
                    <div class="grid grid-cols-4 gap-1 rounded-full bg-kanvas p-1">
                        @foreach (['bawaan' => 'Bawaan', 'gradasi' => 'Gradasi', 'foto' => 'Foto', 'video' => 'Video'] as $nilai => $label)
                            <label class="cursor-pointer rounded-full py-2 text-center text-sm font-semibold transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-hijau"
                                   :class="d.tipe === '{{ $nilai }}' ? 'bg-white text-tinta shadow' : 'text-redup'">
                                <input type="radio" name="tipe" value="{{ $nilai }}" :checked="d.tipe === '{{ $nilai }}'" @change="pilihTipe('{{ $nilai }}')" class="sr-only"> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                    <p x-show="d.tipe === 'bawaan'" class="bantuan">Tampilan asli: tanpa panel, latar abu-abu terang.</p>
                    <x-field-error for="tipe" modal="latar" />
                </fieldset>

                {{-- Gradasi --}}
                <div x-show="d.tipe === 'gradasi'" class="space-y-4">
                    <div>
                        <p class="label">Pilihan cepat</p>
                        <div class="grid grid-cols-3 gap-2 sm:grid-cols-6">
                            <template x-for="p in preset" :key="p.nama">
                                <button type="button" @click="pakaiPreset(p)" :title="p.nama"
                                        class="group flex flex-col items-center gap-1.5 rounded-2xl p-1.5 text-[11px] font-medium text-redup transition hover:bg-kanvas"
                                        :class="d.warna1 === p.w1 && d.warna2 === p.w2 && 'bg-kanvas text-tinta'">
                                    <span class="h-10 w-full rounded-xl border border-black/5" :style="`background-image: linear-gradient(${p.arah}deg, ${p.w1}, ${p.w2})`"></span>
                                    <span x-text="p.nama" class="truncate"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-[1fr_1fr_1.4fr]">
                        <div>
                            <label for="latar_warna1" class="label">Warna 1</label>
                            <div class="flex items-center gap-2 rounded-2xl border border-garis bg-white p-1.5 pr-3">
                                <input id="latar_warna1" type="color" x-model="d.warna1" class="h-9 w-11 cursor-pointer rounded-xl border-0 bg-transparent p-0">
                                <input type="text" name="warna1" x-model="d.warna1" :disabled="d.tipe !== 'gradasi'" maxlength="7" class="w-full border-0 bg-transparent p-0 font-mono text-sm uppercase focus:ring-0" aria-label="Kode warna 1">
                            </div>
                            <x-field-error for="warna1" modal="latar" />
                        </div>
                        <div>
                            <label for="latar_warna2" class="label">Warna 2</label>
                            <div class="flex items-center gap-2 rounded-2xl border border-garis bg-white p-1.5 pr-3">
                                <input id="latar_warna2" type="color" x-model="d.warna2" class="h-9 w-11 cursor-pointer rounded-xl border-0 bg-transparent p-0">
                                <input type="text" name="warna2" x-model="d.warna2" :disabled="d.tipe !== 'gradasi'" maxlength="7" class="w-full border-0 bg-transparent p-0 font-mono text-sm uppercase focus:ring-0" aria-label="Kode warna 2">
                            </div>
                            <x-field-error for="warna2" modal="latar" />
                        </div>
                        <div>
                            <label for="latar_arah" class="label">Arah <span class="font-normal text-redup" x-text="d.arah + '°'"></span></label>
                            <input id="latar_arah" name="arah" type="range" min="0" max="360" step="5" x-model.number="d.arah" :disabled="d.tipe !== 'gradasi'" class="mt-3 w-full accent-hijau">
                        </div>
                    </div>
                </div>

                {{-- Foto / video --}}
                <div x-show="media" class="space-y-4">
                    <div class="grid grid-cols-2 gap-1 rounded-full bg-kanvas p-1">
                        @foreach (['file' => 'Unggah file', 'link' => 'Pakai link'] as $nilai => $label)
                            <label class="cursor-pointer rounded-full py-2 text-center text-sm font-semibold transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-hijau"
                                   :class="d.sumber === '{{ $nilai }}' ? 'bg-white text-tinta shadow' : 'text-redup'">
                                <input type="radio" name="sumber" value="{{ $nilai }}" x-model="d.sumber" :disabled="!media" class="sr-only"> {{ $label }}
                            </label>
                        @endforeach
                    </div>

                    <div x-show="d.sumber === 'file'">
                        <input id="latar_file" name="file" type="file" x-ref="file"
                               :accept="d.tipe === 'video' ? 'video/mp4,video/webm' : 'image/jpeg,image/png,image/webp'"
                               :disabled="!media || d.sumber !== 'file'"
                               x-effect="$store.modal.name; $el.value = ''"
                               @change="pratinjau = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                               class="block w-full text-sm text-redup file:mr-3 file:rounded-full file:border-0 file:bg-tinta file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-black">
                        <p class="bantuan">
                            <span x-show="d.tipe === 'foto'">JPG, PNG, atau WEBP, maksimal 5 MB. Foto mendatar beresolusi tinggi paling bagus.</span>
                            <span x-show="d.tipe === 'video'">MP4 atau WEBM, maksimal 20 MB. Video diputar tanpa suara dan berulang; durasi 10–30 detik paling ringan.</span>
                            <span x-show="d.file_tipe === d.tipe && d.file_url">Kosongkan jika tidak ingin mengganti file saat ini.</span>
                        </p>
                        <x-field-error for="file" modal="latar" />
                    </div>

                    <div x-show="d.sumber === 'link'">
                        <label for="latar_link" class="sr-only">Link</label>
                        <input id="latar_link" name="link" type="url" x-model="d.link" :disabled="!media || d.sumber !== 'link'" class="field"
                               :placeholder="d.tipe === 'video' ? 'https://www.youtube.com/watch?v=...' : 'https://contoh.com/foto.jpg'">
                        <p class="bantuan" x-text="d.tipe === 'video'
                            ? 'Link YouTube, atau link langsung ke file .mp4 / .webm.'
                            : 'Link langsung ke gambar (biasanya berakhiran .jpg, .png, atau .webp).'"></p>
                        <x-field-error for="link" modal="latar" />
                    </div>

                    <div>
                        <label for="latar_gelap" class="label">Lapisan gelap <span class="font-normal text-redup" x-text="d.gelap + '%'"></span></label>
                        <input id="latar_gelap" name="gelap" type="range" min="0" max="80" step="5" x-model.number="d.gelap" :disabled="!media" class="w-full accent-hijau">
                        <p class="bantuan">Naikkan agar tulisan tetap mudah dibaca di atas foto/video yang ramai.</p>
                    </div>
                </div>

                {{-- Warna teks --}}
                <fieldset x-show="d.tipe !== 'bawaan'">
                    <legend class="label">Warna tulisan</legend>
                    <div class="grid grid-cols-2 gap-1 rounded-full bg-kanvas p-1">
                        @foreach (['gelap' => 'Gelap', 'terang' => 'Putih'] as $nilai => $label)
                            <label class="cursor-pointer rounded-full py-2 text-center text-sm font-semibold transition has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-hijau"
                                   :class="d.teks === '{{ $nilai }}' ? 'bg-white text-tinta shadow' : 'text-redup'">
                                <input type="radio" name="teks" value="{{ $nilai }}" x-model="d.teks" class="sr-only"> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                {{-- Catatan: radio "teks" hanya disembunyikan (x-show), jadi tetap terkirim walau tipe bawaan. --}}

                <x-modal-aksi label="Simpan background" />
            </form>
        </x-modal>

        {{-- Visi & misi --}}
        <x-modal name="visi" title="'Edit visi & misi'" lebar="sm:max-w-2xl">
            <form method="POST" action="{{ route('admin.pengaturan.update') }}" class="space-y-5"
                  x-data="{ kirim: false, get d() { return $store.modal.data } }" @submit="kirim = true">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="visi">

                <div>
                    <label for="visi" class="label">Visi</label>
                    <textarea id="visi" name="visi" rows="3" x-model="d.visi" required maxlength="1000" class="field"></textarea>
                    <x-field-error for="visi" modal="visi" />
                </div>
                <div>
                    <label for="misi" class="label">Misi</label>
                    <textarea id="misi" name="misi" rows="7" x-model="d.misi" required maxlength="3000" class="field"></textarea>
                    <p class="bantuan">Tulis satu poin misi per baris.</p>
                    <x-field-error for="misi" modal="visi" />
                </div>

                <x-modal-aksi label="Simpan perubahan" />
            </form>
        </x-modal>

        {{-- Unit / sub-unit --}}
        <x-modal name="unit" title="$store.modal.data.id ? 'Edit unit' : 'Tambah unit'">
            <form method="POST" enctype="multipart/form-data" class="space-y-5"
                  x-data="{ kirim: false, pratinjau: null, get d() { return $store.modal.data } }"
                  x-effect="$store.modal.name; pratinjau = null"
                  @submit="kirim = true"
                  :action="d.id ? @js(route('admin.unit.update', '__ID__')).replace('__ID__', d.id) : @js(route('admin.unit.store'))">
                @csrf
                <input type="hidden" name="_method" :value="d.id ? 'PUT' : 'POST'">
                <input type="hidden" name="_modal" value="unit">
                <input type="hidden" name="_id" :value="d.id">

                <div>
                    <label for="nama_unit" class="label">Nama unit</label>
                    <input id="nama_unit" name="nama_unit" type="text" x-model="d.nama_unit" required maxlength="100" class="field">
                    <x-field-error for="nama_unit" modal="unit" />
                </div>
                <div>
                    <label for="parent_id" class="label">Letak</label>
                    <select id="parent_id" name="parent_id" x-model="d.parent_id" class="field">
                        <option value="">Unit utama (tampil sebagai tab)</option>
                        @foreach ($pilihanUnit->whereNull('parent_id') as $induk)
                            <option value="{{ $induk->id }}" :disabled="d.id == '{{ $induk->id }}'">Sub-unit dari {{ $induk->nama_unit }}</option>
                        @endforeach
                    </select>
                    <x-field-error for="parent_id" modal="unit" />
                </div>
                <div>
                    <label for="unit_deskripsi" class="label">Deskripsi</label>
                    <textarea id="unit_deskripsi" name="deskripsi" rows="3" x-model="d.deskripsi" maxlength="1000" class="field"></textarea>
                    <x-field-error for="deskripsi" modal="unit" />
                </div>

                <div>
                    <label for="unit_logo" class="label">Logo unit</label>
                    <div class="flex items-center gap-4">
                        <div class="ubin h-20 w-28 shrink-0 !rounded-2xl p-3 text-redup">
                            <template x-if="pratinjau || d.logo_url">
                                <img :src="pratinjau || d.logo_url" alt="" class="max-h-full max-w-full object-contain">
                            </template>
                            <template x-if="!(pratinjau || d.logo_url)">
                                <x-ikon name="foto" />
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <input id="unit_logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp"
                                   x-effect="$store.modal.name; $el.value = ''"
                                   @change="pratinjau = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                                   class="block w-full text-sm text-redup file:mr-3 file:rounded-full file:border-0 file:bg-tinta file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-black">
                            <p class="bantuan">
                                PNG transparan paling rapi, maksimal 2 MB. Logo memanjang terdeteksi otomatis.
                                <span x-show="d.id && d.logo_url">Kosongkan jika tidak ingin mengganti.</span>
                            </p>
                            <label x-show="d.id && d.logo_upload && !pratinjau" class="mt-2 flex items-center gap-2 text-sm text-redup">
                                <input type="checkbox" name="hapus_logo" value="1" class="h-4 w-4 rounded border-garis text-hijau focus:ring-hijau"> Hapus logo unggahan
                            </label>
                        </div>
                    </div>
                    <x-field-error for="logo" modal="unit" />
                </div>

                <div>
                    <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-garis p-4 transition has-[:checked]:border-hijau/40 has-[:checked]:bg-hijau-muda/50">
                        {{-- Nilai dikirim lewat input tersembunyi: x-model pada checkbox mengosongkan atribut value-nya. --}}
                        <input type="hidden" name="tampil_hero" :value="d.tampil_hero ? 1 : 0">
                        <input type="checkbox" x-model="d.tampil_hero" class="mt-0.5 h-4 w-4 rounded border-garis text-hijau focus:ring-hijau">
                        <span>
                            <span class="block text-sm font-semibold">Tampilkan logo di hero</span>
                            <span class="block text-xs text-redup">Logo muncul di bagian pembuka beranda (maksimal {{ \App\Support\TataLetakHero::MAKS }} logo). Posisi & garis diatur otomatis.</span>
                        </span>
                    </label>
                    <x-field-error for="tampil_hero" modal="unit" />
                </div>

                <x-modal-aksi label="Simpan unit" />
            </form>
        </x-modal>

        {{-- Produk --}}
        <x-modal name="produk" title="$store.modal.data.id ? 'Edit produk' : 'Tambah produk'">
            <form method="POST" enctype="multipart/form-data" class="space-y-5"
                  x-data="{ kirim: false, pratinjau: null, get d() { return $store.modal.data } }"
                  x-effect="$store.modal.name; pratinjau = null"
                  @submit="kirim = true"
                  :action="d.id ? @js(route('admin.produk.update', '__ID__')).replace('__ID__', d.id) : @js(route('admin.produk.store'))">
                @csrf
                <input type="hidden" name="_method" :value="d.id ? 'PUT' : 'POST'">
                <input type="hidden" name="_modal" value="produk">
                <input type="hidden" name="_id" :value="d.id">

                <div>
                    <label for="unit_id" class="label">Unit</label>
                    <select id="unit_id" name="unit_id" x-model="d.unit_id" required class="field">
                        <option value="" disabled>Pilih unit</option>
                        @foreach ($pilihanUnit->whereNull('parent_id') as $induk)
                            <option value="{{ $induk->id }}">{{ $induk->nama_unit }}</option>
                            @foreach ($pilihanUnit->where('parent_id', $induk->id) as $anak)
                                <option value="{{ $anak->id }}">&nbsp;&nbsp;&nbsp;{{ $induk->nama_unit }} / {{ $anak->nama_unit }}</option>
                            @endforeach
                        @endforeach
                    </select>
                    <x-field-error for="unit_id" modal="produk" />
                </div>
                <div class="grid gap-5 sm:grid-cols-[1fr_10rem]">
                    <div>
                        <label for="nama_produk" class="label">Nama produk</label>
                        <input id="nama_produk" name="nama_produk" type="text" x-model="d.nama_produk" required maxlength="150" class="field">
                        <x-field-error for="nama_produk" modal="produk" />
                    </div>
                    <div>
                        <label for="harga" class="label">Harga (Rp)</label>
                        <input id="harga" name="harga" type="text" inputmode="numeric" x-model="d.harga" required placeholder="25000" class="field">
                        <x-field-error for="harga" modal="produk" />
                    </div>
                </div>
                <div>
                    <label for="produk_deskripsi" class="label">Deskripsi</label>
                    <textarea id="produk_deskripsi" name="deskripsi" rows="3" x-model="d.deskripsi" maxlength="1000" class="field"></textarea>
                    <x-field-error for="deskripsi" modal="produk" />
                </div>
                <div>
                    <label for="foto" class="label">Foto produk</label>
                    <div class="flex items-center gap-4">
                        <div class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-2xl bg-kanvas text-redup">
                            <template x-if="pratinjau || d.foto_url">
                                <img :src="pratinjau || d.foto_url" alt="" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!(pratinjau || d.foto_url)">
                                <x-ikon name="tambah" />
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp"
                                   x-effect="$store.modal.name; $el.value = ''"
                                   @change="pratinjau = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                                   class="block w-full text-sm text-redup file:mr-3 file:rounded-full file:border-0 file:bg-tinta file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-black">
                            <p class="bantuan">JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                            <label x-show="d.id && d.foto_url && !pratinjau" class="mt-2 flex items-center gap-2 text-sm text-redup">
                                <input type="checkbox" name="hapus_foto" value="1" class="h-4 w-4 rounded border-garis text-hijau focus:ring-hijau"> Hapus foto saat ini
                            </label>
                        </div>
                    </div>
                    <x-field-error for="foto" modal="produk" />
                </div>

                <x-modal-aksi label="Simpan produk" />
            </form>
        </x-modal>

        {{-- Judul & deskripsi section cabang --}}
        <x-modal name="teksCabang" title="'Edit judul cabang'">
            <form method="POST" action="{{ route('admin.pengaturan.update') }}" class="space-y-5"
                  x-data="{ kirim: false, get d() { return $store.modal.data } }" @submit="kirim = true">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="teksCabang">

                <div>
                    <label for="cabang_judul" class="label">Judul</label>
                    <input id="cabang_judul" name="cabang_judul" type="text" x-model="d.cabang_judul" required maxlength="80" class="field">
                    <x-field-error for="cabang_judul" modal="teksCabang" />
                </div>
                <div>
                    <label for="cabang_deskripsi" class="label">Deskripsi <span class="font-normal text-redup">(opsional)</span></label>
                    <textarea id="cabang_deskripsi" name="cabang_deskripsi" rows="3" x-model="d.cabang_deskripsi" maxlength="300" class="field"></textarea>
                    <x-field-error for="cabang_deskripsi" modal="teksCabang" />
                </div>

                <x-modal-aksi label="Simpan perubahan" />
            </form>
        </x-modal>

        {{-- Cabang --}}
        <x-modal name="cabang" title="$store.modal.data.id ? 'Edit cabang' : 'Tambah cabang'" lebar="sm:max-w-2xl">
            <form method="POST" enctype="multipart/form-data" class="space-y-5"
                  x-data="{ kirim: false, pratinjau: null, get d() { return $store.modal.data } }"
                  x-effect="$store.modal.name; pratinjau = null"
                  @submit="kirim = true"
                  :action="d.id ? @js(route('admin.cabang.update', '__ID__')).replace('__ID__', d.id) : @js(route('admin.cabang.store'))">
                @csrf
                <input type="hidden" name="_method" :value="d.id ? 'PUT' : 'POST'">
                <input type="hidden" name="_modal" value="cabang">
                <input type="hidden" name="_id" :value="d.id">

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label for="nama_cabang" class="label">Nama cabang</label>
                        <input id="nama_cabang" name="nama_cabang" type="text" x-model="d.nama_cabang" required maxlength="150" class="field">
                        <x-field-error for="nama_cabang" modal="cabang" />
                    </div>
                    <div>
                        <label for="no_whatsapp" class="label">Nomor WhatsApp</label>
                        <input id="no_whatsapp" name="no_whatsapp" type="tel" inputmode="tel" x-model="d.no_whatsapp" required placeholder="081234567890" class="field">
                        <x-field-error for="no_whatsapp" modal="cabang" />
                    </div>
                </div>
                <div>
                    <label for="alamat" class="label">Alamat</label>
                    <textarea id="alamat" name="alamat" rows="2" x-model="d.alamat" required maxlength="500" class="field"></textarea>
                    <x-field-error for="alamat" modal="cabang" />
                </div>
                <div>
                    <label for="link_maps" class="label">Link Google Maps</label>
                    <input id="link_maps" name="link_maps" type="url" x-model="d.link_maps" placeholder="https://maps.app.goo.gl/..." class="field">
                    <p class="bantuan">Peta tidak ditampilkan di kartu, cukup lewat tombol "Buka peta". Boleh dikosongkan.</p>
                    <x-field-error for="link_maps" modal="cabang" />
                </div>

                <div>
                    <label for="cabang_foto" class="label">Foto cabang</label>
                    <div class="flex items-center gap-4">
                        <div class="grid aspect-[4/3] w-32 shrink-0 place-items-center overflow-hidden rounded-2xl bg-kanvas text-redup">
                            <template x-if="pratinjau || d.foto_url">
                                <img :src="pratinjau || d.foto_url" alt="" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!(pratinjau || d.foto_url)">
                                <x-ikon name="foto" />
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <input id="cabang_foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp"
                                   x-effect="$store.modal.name; $el.value = ''"
                                   @change="pratinjau = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                                   class="block w-full text-sm text-redup file:mr-3 file:rounded-full file:border-0 file:bg-tinta file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-black">
                            <p class="bantuan">
                                JPG, PNG, atau WEBP, maksimal 2 MB. Boleh dikosongkan.
                                <span x-show="d.id && d.foto_url">Kosongkan jika tidak ingin mengganti foto.</span>
                            </p>
                            <label x-show="d.id && d.foto_url && !pratinjau" class="mt-2 flex items-center gap-2 text-sm text-redup">
                                <input type="checkbox" name="hapus_foto" value="1" class="h-4 w-4 rounded border-garis text-hijau focus:ring-hijau"> Hapus foto saat ini
                            </label>
                        </div>
                    </div>
                    <x-field-error for="foto" modal="cabang" />
                </div>

                <x-modal-aksi label="Simpan cabang" />
            </form>
        </x-modal>

        {{-- Mitra --}}
        <x-modal name="mitra" title="$store.modal.data.id ? 'Edit mitra' : 'Tambah mitra'">
            <form method="POST" enctype="multipart/form-data" class="space-y-5"
                  x-data="{ kirim: false, pratinjau: null, get d() { return $store.modal.data } }"
                  x-effect="$store.modal.name; pratinjau = null"
                  @submit="kirim = true"
                  :action="d.id ? @js(route('admin.mitra.update', '__ID__')).replace('__ID__', d.id) : @js(route('admin.mitra.store'))">
                @csrf
                <input type="hidden" name="_method" :value="d.id ? 'PUT' : 'POST'">
                <input type="hidden" name="_modal" value="mitra">
                <input type="hidden" name="_id" :value="d.id">

                <div>
                    <label for="nama_mitra" class="label">Nama mitra</label>
                    <input id="nama_mitra" name="nama_mitra" type="text" x-model="d.nama_mitra" required maxlength="150" class="field">
                    <x-field-error for="nama_mitra" modal="mitra" />
                </div>
                <div>
                    <label for="logo" class="label">Logo</label>
                    <div class="flex items-center gap-4">
                        <div class="ubin h-20 w-24 shrink-0 !rounded-2xl p-3 text-redup">
                            <template x-if="pratinjau || d.logo_url">
                                <img :src="pratinjau || d.logo_url" alt="" class="max-h-full max-w-full object-contain">
                            </template>
                            <template x-if="!(pratinjau || d.logo_url)">
                                <x-ikon name="tambah" />
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <input id="logo" name="logo" type="file" accept="image/jpeg,image/png,image/webp" :required="!d.id"
                                   x-effect="$store.modal.name; $el.value = ''"
                                   @change="pratinjau = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                                   class="block w-full text-sm text-redup file:mr-3 file:rounded-full file:border-0 file:bg-tinta file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-black">
                            <p class="bantuan">PNG transparan paling rapi. Maksimal 2 MB.<span x-show="d.id"> Kosongkan jika tidak ingin mengganti.</span></p>
                        </div>
                    </div>
                    <x-field-error for="logo" modal="mitra" />
                </div>

                <x-modal-aksi label="Simpan mitra" />
            </form>
        </x-modal>

        {{-- Berita --}}
        <x-modal name="berita" title="$store.modal.data.id ? 'Edit berita' : 'Tambah berita'" lebar="sm:max-w-2xl">
            <form method="POST" enctype="multipart/form-data" class="space-y-5"
                  x-data="{ kirim: false, pratinjau: null, get d() { return $store.modal.data } }"
                  x-effect="$store.modal.name; pratinjau = null"
                  @submit="kirim = true"
                  :action="d.id ? @js(route('admin.berita.update', '__ID__')).replace('__ID__', d.id) : @js(route('admin.berita.store'))">
                @csrf
                <input type="hidden" name="_method" :value="d.id ? 'PUT' : 'POST'">
                <input type="hidden" name="_modal" value="berita">
                <input type="hidden" name="_id" :value="d.id">

                <div class="grid gap-5 sm:grid-cols-[1fr_11rem]">
                    <div>
                        <label for="berita_judul" class="label">Judul</label>
                        <input id="berita_judul" name="judul" type="text" x-model="d.judul" required maxlength="200" class="field">
                        <x-field-error for="judul" modal="berita" />
                    </div>
                    <div>
                        <label for="berita_terbit" class="label">Tanggal terbit</label>
                        <input id="berita_terbit" name="terbit_pada" type="date" x-model="d.terbit_pada" required class="field">
                        <x-field-error for="terbit_pada" modal="berita" />
                    </div>
                </div>
                <div>
                    <label for="berita_ringkasan" class="label">Ringkasan <span class="font-normal text-redup">(opsional)</span></label>
                    <textarea id="berita_ringkasan" name="ringkasan" rows="2" x-model="d.ringkasan" maxlength="300" class="field resize-none"></textarea>
                    <p class="bantuan flex justify-between gap-4">
                        <span>Tampil di kartu berita. Jika kosong, diambil dari awal isi berita.</span>
                        <span class="shrink-0" x-text="(d.ringkasan || '').length + '/300'"></span>
                    </p>
                    <x-field-error for="ringkasan" modal="berita" />
                </div>
                <div>
                    <label for="berita_isi" class="label">Isi berita</label>
                    <textarea id="berita_isi" name="isi" rows="9" x-model="d.isi" required maxlength="20000" class="field"></textarea>
                    <p class="bantuan">Pisahkan paragraf dengan satu baris kosong.</p>
                    <x-field-error for="isi" modal="berita" />
                </div>
                <div>
                    <label for="berita_foto" class="label">Foto sampul</label>
                    <div class="flex items-center gap-4">
                        <div class="grid aspect-video w-32 shrink-0 place-items-center overflow-hidden rounded-2xl bg-kanvas text-redup">
                            <template x-if="pratinjau || d.foto_url">
                                <img :src="pratinjau || d.foto_url" alt="" class="h-full w-full object-cover">
                            </template>
                            <template x-if="!(pratinjau || d.foto_url)">
                                <x-ikon name="foto" />
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <input id="berita_foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp"
                                   x-effect="$store.modal.name; $el.value = ''"
                                   @change="pratinjau = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                                   class="block w-full text-sm text-redup file:mr-3 file:rounded-full file:border-0 file:bg-tinta file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-black">
                            <p class="bantuan">JPG, PNG, atau WEBP, maksimal 2 MB. Foto mendatar (16:9) paling rapi.</p>
                            <label x-show="d.id && d.foto_url && !pratinjau" class="mt-2 flex items-center gap-2 text-sm text-redup">
                                <input type="checkbox" name="hapus_foto" value="1" class="h-4 w-4 rounded border-garis text-hijau focus:ring-hijau"> Hapus foto saat ini
                            </label>
                        </div>
                    </div>
                    <x-field-error for="foto" modal="berita" />
                </div>

                <x-modal-aksi label="Simpan berita" />
            </form>
        </x-modal>

        {{-- Tentang kami: teks & angka --}}
        <x-modal name="tentang" title="'Edit tentang kami'" lebar="sm:max-w-2xl">
            <form method="POST" action="{{ route('admin.tentang.update') }}" class="space-y-5"
                  x-data="{ kirim: false, get d() { return $store.modal.data } }" @submit="kirim = true">
                @csrf
                @method('PUT')
                <input type="hidden" name="_modal" value="tentang">

                <div>
                    <label for="tentang_judul" class="label">Judul</label>
                    <input id="tentang_judul" name="tentang_judul" type="text" x-model="d.tentang_judul" required maxlength="120" class="field">
                    <x-field-error for="tentang_judul" modal="tentang" />
                </div>
                <div>
                    <label for="tentang_isi" class="label">Cerita singkat</label>
                    <textarea id="tentang_isi" name="tentang_isi" rows="5" x-model="d.tentang_isi" required maxlength="2000" class="field"></textarea>
                    <x-field-error for="tentang_isi" modal="tentang" />
                </div>

                <fieldset>
                    <legend class="label">Angka pencapaian</legend>
                    <div class="space-y-2">
                        <template x-for="(baris, i) in d.statistik" :key="i">
                            <div class="grid grid-cols-[7rem_1fr] gap-2">
                                <input type="text" :name="`statistik[${i}][angka]`" x-model="baris.angka" maxlength="12" placeholder="20+" class="field" :aria-label="`Angka ${i + 1}`">
                                <input type="text" :name="`statistik[${i}][label]`" x-model="baris.label" maxlength="40" placeholder="Santri terlibat" class="field" :aria-label="`Keterangan angka ${i + 1}`">
                            </div>
                        </template>
                    </div>
                    <p class="bantuan">Kosongkan semua untuk memakai angka otomatis: jumlah unit usaha, cabang, dan produk.</p>
                    <x-field-error for="statistik.*" modal="tentang" />
                </fieldset>

                <x-modal-aksi label="Simpan perubahan" />
            </form>
        </x-modal>

        {{-- Tentang kami: foto --}}
        <x-modal name="fotoTentang" title="'Foto tentang kami'" lebar="sm:max-w-2xl">
            @php $sisaFoto = \App\Models\FotoTentang::MAKS - $fotoTentang->count(); @endphp

            @if ($fotoTentang->isNotEmpty())
                <ul class="grid grid-cols-3 gap-3 sm:grid-cols-6">
                    @foreach ($fotoTentang as $foto)
                        <li class="relative aspect-[4/5] overflow-hidden rounded-2xl bg-kanvas">
                            <img src="{{ $foto->foto_url }}" alt="Foto {{ $loop->iteration }}" class="h-full w-full object-cover">
                            <button type="button" class="ikon-admin-hapus absolute right-1.5 top-1.5 h-7 w-7" aria-label="Hapus foto {{ $loop->iteration }}"
                                @click="$store.modal.open('hapus', @js([
                                    'jenis' => 'foto',
                                    'label' => 'Foto ' . $loop->iteration,
                                    'action' => route('admin.tentang.foto.destroy', $foto),
                                ]))">
                                <x-ikon name="hapus" class="h-3.5 w-3.5" />
                            </button>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="rounded-2xl bg-kanvas p-4 text-sm text-redup">Belum ada foto. Selama kosong, logo unit usaha yang tampil melayang.</p>
            @endif

            @if ($sisaFoto > 0)
                <form method="POST" action="{{ route('admin.tentang.foto.store') }}" enctype="multipart/form-data" class="mt-6 space-y-4"
                      x-data="{ kirim: false, pratinjau: [] }"
                      x-effect="$store.modal.name; pratinjau = []"
                      @submit="kirim = true">
                    @csrf
                    <input type="hidden" name="_modal" value="fotoTentang">

                    <div>
                        <label for="tentang_foto" class="label">Tambah foto <span class="font-normal text-redup">(bisa pilih beberapa sekaligus, sisa {{ $sisaFoto }})</span></label>
                        <input id="tentang_foto" name="foto[]" type="file" multiple required accept="image/jpeg,image/png,image/webp"
                               x-effect="$store.modal.name; $el.value = ''"
                               @change="pratinjau = [...$event.target.files].map((f) => URL.createObjectURL(f))"
                               class="block w-full text-sm text-redup file:mr-3 file:rounded-full file:border-0 file:bg-tinta file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-black">
                        <p class="bantuan">JPG, PNG, atau WEBP, maksimal 2 MB per foto. Foto tegak (potret) paling rapi.</p>
                        <x-field-error for="foto" modal="fotoTentang" />
                        <x-field-error for="foto.*" modal="fotoTentang" />
                    </div>

                    <div x-show="pratinjau.length" class="grid grid-cols-3 gap-3 sm:grid-cols-6">
                        <template x-for="src in pratinjau" :key="src">
                            <div class="aspect-[4/5] overflow-hidden rounded-2xl bg-kanvas"><img :src="src" alt="" class="h-full w-full object-cover"></div>
                        </template>
                    </div>

                    <x-modal-aksi label="Unggah foto" />
                </form>
            @else
                <p class="mt-6 rounded-2xl bg-emas-muda p-4 text-sm font-medium text-emas-tua">Sudah {{ \App\Models\FotoTentang::MAKS }} foto (maksimal). Hapus salah satu untuk mengganti.</p>
            @endif
        </x-modal>

        {{-- Konfirmasi hapus (dipakai semua jenis data) --}}
        <x-modal name="hapus" title="'Hapus ' + ($store.modal.data.jenis || 'data') + '?'" lebar="sm:max-w-md">
            <form method="POST" :action="$store.modal.data.action" x-data="{ kirim: false }" @submit="kirim = true">
                @csrf
                @method('DELETE')

                <p class="text-[15px] leading-relaxed text-redup">
                    <strong class="font-semibold text-tinta" x-text="$store.modal.data.label"></strong> akan dihapus permanen dan tidak bisa dikembalikan.
                </p>
                <p x-show="$store.modal.data.catatan" x-text="$store.modal.data.catatan" class="mt-3 rounded-2xl bg-red-50 p-3 text-sm font-medium text-red-700"></p>

                <div class="mt-6">
                    <x-modal-aksi label="Ya, hapus" :bahaya="true" />
                </div>
            </form>
        </x-modal>
    @endauth
</body>
</html>
