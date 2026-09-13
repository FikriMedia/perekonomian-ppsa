<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CabangRequest;
use App\Models\Cabang;
use Illuminate\Http\RedirectResponse;

class CabangController extends Controller
{
    public function store(CabangRequest $request): RedirectResponse
    {
        Cabang::create([
            ...$this->dataUtama($request),
            'media_type' => Cabang::MEDIA_FILE,
            'media_path' => $request->file('foto')?->store('cabang', 'public'),
        ]);

        return $this->kembali('Cabang ditambahkan.');
    }

    public function update(CabangRequest $request, Cabang $cabang): RedirectResponse
    {
        $data = $this->dataUtama($request);
        // Cabang lama yang menyimpan embed peta sebagai media: pindahkan ke tombol peta agar tidak hilang.
        $data['link_maps'] = $data['link_maps'] ?? $cabang->peta_url;
        $data['media_type'] = Cabang::MEDIA_FILE;

        // Ganti foto, hapus foto, atau buang media lama yang bukan foto (video / link YouTube / embed peta).
        if ($request->hasFile('foto') || $request->boolean('hapus_foto') || ! $cabang->foto_url) {
            $cabang->hapusFileMedia();
            $data['media_path'] = $request->file('foto')?->store('cabang', 'public');
        }
        // Tanpa unggahan baru => foto lama dipertahankan.

        $cabang->update($data);

        return $this->kembali('Cabang diperbarui.');
    }

    public function destroy(Cabang $cabang): RedirectResponse
    {
        $cabang->delete(); // file media ikut terhapus (Cabang::booted)

        return $this->kembali('Cabang dihapus.');
    }

    private function dataUtama(CabangRequest $request): array
    {
        return $request->safe()->only(['nama_cabang', 'alamat', 'link_maps', 'no_whatsapp']);
    }

    private function kembali(string $pesan): RedirectResponse
    {
        return back()->withFragment('cabang')->with('status', $pesan);
    }
}
