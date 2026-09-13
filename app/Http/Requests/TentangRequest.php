<?php

namespace App\Http\Requests;

class TentangRequest extends ModalFormRequest
{
    protected string $section = 'tentang-kami';

    public function rules(): array
    {
        return [
            'tentang_judul'        => ['required', 'string', 'max:120'],
            'tentang_isi'          => ['required', 'string', 'max:2000'],
            'statistik'            => ['nullable', 'array', 'max:3'],
            'statistik.*.angka'    => ['nullable', 'string', 'max:12', 'required_with:statistik.*.label'],
            'statistik.*.label'    => ['nullable', 'string', 'max:40', 'required_with:statistik.*.angka'],
        ];
    }

    public function attributes(): array
    {
        return [
            'tentang_judul'     => 'judul',
            'tentang_isi'       => 'cerita',
            'statistik.*.angka' => 'angka',
            'statistik.*.label' => 'keterangan angka',
        ];
    }
}
