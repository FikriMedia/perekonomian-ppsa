<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pengaturan;
use App\Support\TataLetakHero;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LogoHeroController extends Controller
{
    /** Simpan urutan logo unit pada posisi hero (hasil tukar posisi oleh admin). */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'urutan'   => ['required', 'array', 'max:' . TataLetakHero::MAKS],
            'urutan.*' => ['required', 'integer', 'distinct', Rule::exists('units', 'id')],
        ]);

        Pengaturan::simpan('hero_urutan_logo', json_encode(array_map('intval', array_values($data['urutan']))));

        return back()->withFragment('beranda')->with('status', 'Posisi logo disimpan.');
    }
}
