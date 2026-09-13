<?php

namespace App\Http\Requests;

class BeritaRequest extends ModalFormRequest
{
    protected string $section = 'berita';

    public function rules(): array
    {
        return [
            'judul'       => ['required', 'string', 'max:200'],
            'ringkasan'   => ['nullable', 'string', 'max:300'],
            'isi'         => ['required', 'string', 'max:20000'],
            'terbit_pada' => ['required', 'date'],
            'foto'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // 2 MB
            'hapus_foto'  => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'isi'         => 'isi berita',
            'terbit_pada' => 'tanggal terbit',
        ];
    }

    public function messages(): array
    {
        return [
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
        ];
    }
}
