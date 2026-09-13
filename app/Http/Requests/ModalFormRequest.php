<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Basis request untuk form di dalam modal admin.
 *
 * Saat validasi gagal, halaman kembali ke section asal (#fragment). Hidden input
 * `_modal` & `_id` dari form ikut tersimpan di old(), sehingga welcome.blade.php
 * bisa membuka ulang modal yang sama lengkap dengan pesan error.
 */
abstract class ModalFormRequest extends FormRequest
{
    /** ID section tujuan setelah redirect, mis. 'unit' => /#unit */
    protected string $section = '';

    public function authorize(): bool
    {
        // Route sudah dibungkus middleware auth.
        return $this->user() !== null;
    }

    protected function getRedirectUrl(): string
    {
        $url = parent::getRedirectUrl();

        return $this->section ? strtok($url, '#') . '#' . $this->section : $url;
    }
}
