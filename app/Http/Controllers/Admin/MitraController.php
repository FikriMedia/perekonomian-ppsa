<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\MitraRequest;
use App\Models\Mitra;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class MitraController extends Controller
{
    public function store(MitraRequest $request): RedirectResponse
    {
        Mitra::create([
            'nama_mitra' => $request->validated('nama_mitra'),
            'logo_path'  => $request->file('logo')->store('mitra', 'public'),
        ]);

        return $this->kembali('Mitra ditambahkan.');
    }

    public function update(MitraRequest $request, Mitra $mitra): RedirectResponse
    {
        $data = ['nama_mitra' => $request->validated('nama_mitra')];

        if ($request->hasFile('logo')) {
            if ($mitra->logo_path) {
                Storage::disk('public')->delete($mitra->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('mitra', 'public');
        }

        $mitra->update($data);

        return $this->kembali('Mitra diperbarui.');
    }

    public function destroy(Mitra $mitra): RedirectResponse
    {
        $mitra->delete(); // logo ikut terhapus (Mitra::booted)

        return $this->kembali('Mitra dihapus.');
    }

    private function kembali(string $pesan): RedirectResponse
    {
        return back()->withFragment('mitra')->with('status', $pesan);
    }
}
