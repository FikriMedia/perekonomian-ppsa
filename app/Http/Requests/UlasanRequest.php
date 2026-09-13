<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Form ulasan publik (tanpa login). */
class UlasanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_pengunjung' => ['required', 'string', 'min:2', 'max:100'],
            'rating'          => ['required', 'integer', 'between:1,5'],
            'komentar'        => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    protected function getRedirectUrl(): string
    {
        return strtok(parent::getRedirectUrl(), '#') . '#ulasan';
    }

    public function attributes(): array
    {
        return [
            'nama_pengunjung' => 'nama',
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Pilih jumlah bintang.',
        ];
    }
}
