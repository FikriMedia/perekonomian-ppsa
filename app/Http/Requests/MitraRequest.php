<?php

namespace App\Http\Requests;

class MitraRequest extends ModalFormRequest
{
    protected string $section = 'mitra';

    public function rules(): array
    {
        $logoWajib = $this->route('mitra') === null ? 'required' : 'nullable';

        return [
            'nama_mitra' => ['required', 'string', 'max:150'],
            'logo'       => [$logoWajib, 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // 2 MB
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_mitra' => 'nama mitra',
        ];
    }

    public function messages(): array
    {
        return [
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
        ];
    }
}
