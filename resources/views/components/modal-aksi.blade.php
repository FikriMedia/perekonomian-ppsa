{{-- Tombol Batal + Simpan untuk form modal. Form induk harus punya state Alpine `kirim`. --}}
@props(['label' => 'Simpan', 'bahaya' => false])

<div class="flex flex-col-reverse gap-2 pt-3 sm:flex-row sm:justify-end">
    <button type="button" class="btn-putih" @click="$store.modal.close()">Batal</button>
    <button type="submit" :disabled="kirim" class="{{ $bahaya ? 'btn bg-red-600 text-white hover:bg-red-700' : 'btn-gelap' }}">
        <span x-show="!kirim">{{ $label }}</span>
        <span x-show="kirim" x-cloak>Memproses...</span>
    </button>
</div>
