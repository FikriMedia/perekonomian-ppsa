<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProdukRequest;
use App\Models\Produk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class ProdukController extends Controller
{
    public function store(ProdukRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['foto', 'hapus_foto']);

        if ($request->hasFile('foto')) {
            $data['foto_path'] = $request->file('foto')->store('produk', 'public');
        }

        $produk = Produk::create($data);

        return $this->kembali($produk, 'Produk ditambahkan.');
    }

    public function update(ProdukRequest $request, Produk $produk): RedirectResponse
    {
        $data = $request->safe()->except(['foto', 'hapus_foto']);
        $fotoLama = $produk->foto_path;

        if ($request->hasFile('foto')) {
            $data['foto_path'] = $request->file('foto')->store('produk', 'public');
        } elseif ($request->boolean('hapus_foto')) {
            $data['foto_path'] = null;
        }

        $produk->update($data);

        if ($fotoLama && $fotoLama !== $produk->foto_path) {
            Storage::disk('public')->delete($fotoLama);
        }

        return $this->kembali($produk, 'Produk diperbarui.');
    }

    public function destroy(Produk $produk): RedirectResponse
    {
        $tab = $produk->unit?->root_id;
        $produk->delete(); // foto ikut terhapus (Produk::booted)

        return back()->withFragment('unit')->with([
            'status' => 'Produk dihapus.',
            'tab'    => $tab,
        ]);
    }

    private function kembali(Produk $produk, string $pesan): RedirectResponse
    {
        return back()->withFragment('unit')->with([
            'status' => $pesan,
            'tab'    => $produk->unit->root_id,
        ]);
    }
}
