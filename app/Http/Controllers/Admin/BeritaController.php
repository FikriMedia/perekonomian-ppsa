<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BeritaRequest;
use App\Models\Berita;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class BeritaController extends Controller
{
    public function store(BeritaRequest $request): RedirectResponse
    {
        Berita::create([
            ...$request->safe()->only(['judul', 'ringkasan', 'isi', 'terbit_pada']),
            'foto_path' => $request->file('foto')?->store('berita', 'public'),
        ]);

        return $this->kembali('Berita diterbitkan.');
    }

    public function update(BeritaRequest $request, Berita $berita): RedirectResponse
    {
        $data = $request->safe()->only(['judul', 'ringkasan', 'isi', 'terbit_pada']);

        if ($request->hasFile('foto') || $request->boolean('hapus_foto')) {
            if ($berita->foto_path) {
                Storage::disk('public')->delete($berita->foto_path);
            }
            $data['foto_path'] = $request->file('foto')?->store('berita', 'public');
        }

        $berita->update($data);

        return $this->kembali('Berita diperbarui.');
    }

    public function destroy(Berita $berita): RedirectResponse
    {
        $berita->delete(); // foto ikut terhapus (Berita::booted)

        return $this->kembali('Berita dihapus.');
    }

    private function kembali(string $pesan): RedirectResponse
    {
        return back()->withFragment('berita')->with('status', $pesan);
    }
}
