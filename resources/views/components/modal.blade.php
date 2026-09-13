{{--
    Modal Alpine yang dikendalikan $store.modal.
    Pemakaian:
        <x-modal name="produk" title="$store.modal.data.id ? 'Edit produk' : 'Tambah produk'"> ...form... </x-modal>
    `title` adalah ekspresi JavaScript (dirender lewat x-text).
--}}
@props(['name', 'title', 'lebar' => 'sm:max-w-lg'])

<div
    x-cloak
    x-show="$store.modal.name === @js($name)"
    x-transition.opacity.duration.200ms
    x-trap.inert.noscroll="$store.modal.name === @js($name)"
    x-effect="if ($store.modal.name === @js($name)) setTimeout(() => $el.querySelector('input:not([type=hidden]):not([disabled]), textarea, select')?.focus(), 60)"
    @keydown.escape.window="$store.modal.name === @js($name) && $store.modal.close()"
    class="fixed inset-0 z-[70] flex items-end justify-center p-3 sm:items-center sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-{{ $name }}-judul"
>
    <div class="absolute inset-0 bg-tinta/40 backdrop-blur-[3px]" @click="$store.modal.close()"></div>

    <div
        x-show="$store.modal.name === @js($name)"
        x-transition:enter="transition duration-200 ease-out"
        x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-0 sm:scale-95"
        x-transition:enter-end="translate-y-0 opacity-100 sm:scale-100"
        class="relative max-h-[92vh] w-full {{ $lebar }} overflow-y-auto rounded-[28px] bg-white p-6 shadow-[0_40px_80px_-24px_rgba(20,35,26,.45)] sm:p-8"
    >
        <div class="mb-6 flex items-start justify-between gap-4">
            <h2 id="modal-{{ $name }}-judul" class="font-display text-2xl font-semibold tracking-rapat" x-text="{{ $title }}"></h2>
            <button type="button" @click="$store.modal.close()" class="-mr-2 -mt-1 grid h-9 w-9 shrink-0 place-items-center rounded-full text-redup hover:bg-kanvas hover:text-tinta" aria-label="Tutup">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
            </button>
        </div>

        {{ $slot }}
    </div>
</div>
