<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UnitRequest;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;

class UnitController extends Controller
{
    public function store(UnitRequest $request): RedirectResponse
    {
        $unit = Unit::create([...$this->dataUtama($request), ...$this->dataLogo($request)]);

        return $this->kembali($unit, 'Unit ditambahkan.');
    }

    public function update(UnitRequest $request, Unit $unit): RedirectResponse
    {
        $data = $this->dataUtama($request);

        if ($request->hasFile('logo') || $request->boolean('hapus_logo')) {
            $unit->hapusLogo();
            $data = [...$data, ...$this->dataLogo($request)];
        }

        $unit->update($data);

        return $this->kembali($unit, 'Unit diperbarui.');
    }

    private function dataUtama(UnitRequest $request): array
    {
        return [
            ...$request->safe()->only(['nama_unit', 'deskripsi', 'parent_id']),
            'tampil_hero' => $request->boolean('tampil_hero'),
        ];
    }

    /** Simpan logo unggahan (jika ada) dan tandai logo memanjang dari rasio gambarnya. */
    private function dataLogo(UnitRequest $request): array
    {
        $file = $request->file('logo');
        if (! $file) {
            return ['logo_path' => null, 'logo_lebar' => false];
        }

        [$lebar, $tinggi] = getimagesize($file->getRealPath()) ?: [1, 1];

        return [
            'logo_path'  => $file->store('unit', 'public'),
            'logo_lebar' => $tinggi > 0 && $lebar / $tinggi >= 1.8,
        ];
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $tab = $unit->parent_id; // tetap di tab induk jika yang dihapus sub-unit
        $unit->delete();         // sub-unit, produk & fotonya ikut terhapus (Unit::booted)

        return back()->withFragment('unit')->with([
            'status' => 'Unit dihapus.',
            'tab'    => $tab,
        ]);
    }

    private function kembali(Unit $unit, string $pesan): RedirectResponse
    {
        return back()->withFragment('unit')->with([
            'status' => $pesan,
            'tab'    => $unit->root_id,
        ]);
    }
}
