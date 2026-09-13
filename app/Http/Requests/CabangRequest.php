<?php

namespace App\Http\Requests;

class CabangRequest extends ModalFormRequest
{
    protected string $section = 'cabang';

    public function rules(): array
    {
        // Foto opsional: kartu tanpa foto menampilkan gambar pengganti.
        return [
            'nama_cabang' => ['required', 'string', 'max:150'],
            'alamat'      => ['required', 'string', 'max:500'],
            'link_maps'   => ['nullable', 'url:http,https', 'max:2000'],
            'no_whatsapp' => ['required', 'string', 'regex:/^(\+?62|0)8\d{7,12}$/'],
            'foto'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // 2 MB
            'hapus_foto'  => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('no_whatsapp')) {
            $this->merge(['no_whatsapp' => preg_replace('/[\s\-().]/', '', $this->input('no_whatsapp'))]);
        }
    }

    public function attributes(): array
    {
        return [
            'nama_cabang' => 'nama cabang',
            'link_maps'   => 'link Google Maps',
            'no_whatsapp' => 'nomor WhatsApp',
        ];
    }

    public function messages(): array
    {
        return [
            'no_whatsapp.regex' => 'Nomor WhatsApp harus diawali 08 atau 62, contoh 081234567890.',
            'foto.max'          => 'Ukuran foto maksimal 2 MB.',
        ];
    }
}
