<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PengaturanRequest;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;

class PengaturanController extends Controller
{
    public function update(PengaturanRequest $request): RedirectResponse
    {
        $data = $request->validated();

        foreach ($data as $key => $value) {
            Pengaturan::simpan($key, $value);
        }

        $section = match (true) {
            array_key_exists('hero_judul', $data)   => 'beranda',
            array_key_exists('cabang_judul', $data) => 'cabang',
            default                                 => 'visi-misi',
        };

        return back()->withFragment($section)->with('status', 'Perubahan disimpan.');
    }
}
