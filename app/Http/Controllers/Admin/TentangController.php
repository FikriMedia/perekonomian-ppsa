<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\FotoTentangRequest;
use App\Http\Requests\TentangRequest;
use App\Models\FotoTentang;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;

class TentangController extends Controller
{
    public function update(TentangRequest $request): RedirectResponse
    {
        Pengaturan::simpan('tentang_judul', $request->validated('tentang_judul'));
        Pengaturan::simpan('tentang_isi', $request->validated('tentang_isi'));

        // Baris angka yang kosong dibuang; jika semua kosong, halaman memakai angka otomatis.
        $statistik = collect($request->validated('statistik', []))
            ->filter(fn ($baris) => filled($baris['angka'] ?? null) && filled($baris['label'] ?? null))
            ->map(fn ($baris) => ['angka' => trim($baris['angka']), 'label' => trim($baris['label'])])
            ->values();

        Pengaturan::simpan('tentang_statistik', $statistik->isEmpty() ? null : $statistik->toJson());

        return $this->kembali('Tentang kami diperbarui.');
    }

    public function storeFoto(FotoTentangRequest $request): RedirectResponse
    {
        foreach ($request->file('foto') as $file) {
            FotoTentang::create(['foto_path' => $file->store('tentang', 'public')]);
        }

        return $this->kembali('Foto ditambahkan.');
    }

    public function destroyFoto(FotoTentang $foto): RedirectResponse
    {
        $foto->delete(); // file ikut terhapus (FotoTentang::booted)

        return $this->kembali('Foto dihapus.');
    }

    private function kembali(string $pesan): RedirectResponse
    {
        return back()->withFragment('tentang-kami')->with('status', $pesan);
    }
}
