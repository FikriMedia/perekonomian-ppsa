<?php

namespace App\Http\Requests;

class ProdukRequest extends ModalFormRequest
{
    protected string $section = 'unit';

    public function rules(): array
    {
        return [
            'unit_id'     => ['required', 'integer', 'exists:units,id'],
            'nama_produk' => ['required', 'string', 'max:150'],
            'deskripsi'   => ['nullable', 'string', 'max:1000'],
            'harga'       => ['required', 'integer', 'min:0', 'max:999999999'],
            'foto'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // 2 MB
            'hapus_foto'  => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // "25.000" / "Rp25.000" => 25000
        if ($this->has('harga')) {
            $this->merge(['harga' => preg_replace('/\D/', '', (string) $this->input('harga'))]);
        }
    }

    public function attributes(): array
    {
        return [
            'unit_id'     => 'unit',
            'nama_produk' => 'nama produk',
        ];
    }

    public function messages(): array
    {
        return [
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
        ];
    }
}
