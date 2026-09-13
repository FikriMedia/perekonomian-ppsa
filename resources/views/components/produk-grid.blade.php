{{-- Grid kartu produk untuk satu unit / sub-unit. --}}
@props(['produks', 'logo' => null, 'logoLebar' => false])

@if ($produks->isEmpty())
    <div class="mt-6 rounded-3xl border border-dashed border-garis px-6 py-10 text-center text-[15px] text-redup">
        Belum ada produk di sini.
    </div>
@else
    <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($produks as $produk)
            <article class="relative flex flex-col overflow-hidden rounded-3xl border border-garis bg-white">
                <div class="relative aspect-[4/3] bg-kanvas">
                    @if ($produk->foto_url)
                        <img src="{{ $produk->foto_url }}" alt="{{ $produk->nama_produk }}" loading="lazy" class="h-full w-full object-cover">
                    @elseif ($logo)
                        <div class="grid h-full place-items-center">
                            <img src="{{ $logo }}" alt="" @class(['object-contain opacity-25 grayscale', 'h-16 w-16' => ! $logoLebar, 'h-10 w-36' => $logoLebar])>
                        </div>
                    @endif

                    @auth
                        <div class="absolute right-3 top-3 flex gap-1.5">
                            <button type="button" class="ikon-admin" aria-label="Edit {{ $produk->nama_produk }}"
                                @click="$store.modal.open('produk', @js([
                                    'id' => (string) $produk->id,
                                    'unit_id' => (string) $produk->unit_id,
                                    'nama_produk' => $produk->nama_produk,
                                    'harga' => (string) $produk->harga,
                                    'deskripsi' => $produk->deskripsi,
                                    'foto_url' => $produk->foto_url,
                                ]))">
                                <x-ikon name="pensil" class="h-4 w-4" />
                            </button>
                            <button type="button" class="ikon-admin-hapus" aria-label="Hapus {{ $produk->nama_produk }}"
                                @click="$store.modal.open('hapus', @js([
                                    'jenis' => 'produk',
                                    'label' => $produk->nama_produk,
                                    'action' => route('admin.produk.destroy', $produk),
                                ]))">
                                <x-ikon name="hapus" class="h-4 w-4" />
                            </button>
                        </div>
                    @endauth
                </div>

                <div class="flex flex-1 flex-col p-5">
                    <h4 class="font-display text-lg font-semibold leading-snug tracking-[-0.02em]">{{ $produk->nama_produk }}</h4>
                    @if ($produk->deskripsi)
                        <p class="mt-1.5 line-clamp-2 text-sm leading-relaxed text-redup">{{ $produk->deskripsi }}</p>
                    @endif
                    <p class="mt-auto pt-4 font-display text-lg font-semibold tracking-[-0.02em] text-hijau">{{ $produk->harga_rupiah }}</p>
                </div>
            </article>
        @endforeach
    </div>
@endif
