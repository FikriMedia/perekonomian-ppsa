{{-- Pesan error hanya untuk modal yang terakhir dikirim (old('_modal')), agar tidak bocor ke modal lain dengan nama field sama. --}}
@props(['for', 'modal'])

@if (old('_modal') === $modal)
    @error($for)
        <p class="mt-1.5 text-sm font-medium text-red-600">{{ $message }}</p>
    @enderror
@endif
