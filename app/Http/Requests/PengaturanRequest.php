<?php

namespace App\Http\Requests;

class PengaturanRequest extends ModalFormRequest
{
    protected string $section = 'visi-misi';

    public function rules(): array
    {
        return [
            'hero_judul'     => ['sometimes', 'required', 'string', 'max:120'],
            'hero_deskripsi' => ['sometimes', 'nullable', 'string', 'max:400'],
            'whatsapp'       => ['sometimes', 'nullable', 'string', 'regex:/^(\+?62|0)8\d{7,12}$/'],
            'visi'           => ['sometimes', 'required', 'string', 'max:1000'],
            'misi'           => ['sometimes', 'required', 'string', 'max:3000'],
            'cabang_judul'     => ['sometimes', 'required', 'string', 'max:80'],
            'cabang_deskripsi' => ['sometimes', 'nullable', 'string', 'max:300'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('whatsapp')) {
            $this->merge(['whatsapp' => preg_replace('/[\s\-().]/', '', $this->input('whatsapp'))]);
        }

        if ($this->has('hero_judul')) {
            $this->section = 'beranda';
        } elseif ($this->has('cabang_judul')) {
            $this->section = 'cabang';
        }
    }

    public function messages(): array
    {
        return [
            'whatsapp.regex' => 'Nomor WhatsApp harus diawali 08 atau 62, contoh 081234567890.',
        ];
    }
}
