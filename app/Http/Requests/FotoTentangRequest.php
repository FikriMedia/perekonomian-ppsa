<?php

namespace App\Http\Requests;

use App\Models\FotoTentang;
use Illuminate\Validation\Validator;

class FotoTentangRequest extends ModalFormRequest
{
    protected string $section = 'tentang-kami';

    public function rules(): array
    {
        return [
            'foto'   => ['required', 'array', 'min:1'],
            'foto.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'], // 2 MB per foto
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $sisa = FotoTentang::MAKS - FotoTentang::count();

                if (count((array) $this->file('foto')) > $sisa) {
                    $validator->errors()->add('foto', $sisa > 0
                        ? "Maksimal " . FotoTentang::MAKS . " foto. Anda hanya bisa menambah {$sisa} foto lagi."
                        : 'Sudah ada ' . FotoTentang::MAKS . ' foto. Hapus salah satu sebelum menambah.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'foto.required' => 'Pilih minimal satu foto.',
            'foto.*.image'  => 'File harus berupa gambar.',
            'foto.*.mimes'  => 'Foto harus JPG, PNG, atau WEBP.',
            'foto.*.max'    => 'Ukuran tiap foto maksimal 2 MB.',
        ];
    }
}
