<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\LatarHeroRequest;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class LatarHeroController extends Controller
{
    public function update(LatarHeroRequest $request): RedirectResponse
    {
        $lama = Pengaturan::latarHero(Pengaturan::ambil(['hero_background'])['hero_background'] ?? null);
        $tipe = $request->validated('tipe');

        $baru = [
            'tipe'   => $tipe,
            'warna1' => strtoupper($request->validated('warna1') ?? $lama['warna1']),
            'warna2' => strtoupper($request->validated('warna2') ?? $lama['warna2']),
            'arah'   => (int) ($request->validated('arah') ?? $lama['arah']),
            'sumber' => $request->validated('sumber') ?? 'file',
            'path'   => null,
            'link'   => null,
            'gelap'  => (int) ($request->validated('gelap') ?? $lama['gelap']),
            'teks'   => $request->validated('teks'),
        ];

        if (in_array($tipe, ['foto', 'video'], true)) {
            if ($baru['sumber'] === 'link') {
                $baru['link'] = $request->validated('link');
            } elseif ($request->hasFile('file')) {
                $baru['path'] = $request->file('file')->store('hero', 'public');
            } else {
                $baru['path'] = $lama['path']; // file lama bertipe sama dipertahankan (dicek di request)
            }
        }

        // File lama yang tidak dipakai lagi dihapus dari storage.
        if ($lama['path'] && $lama['path'] !== $baru['path']) {
            Storage::disk('public')->delete($lama['path']);
        }

        Pengaturan::simpan('hero_background', json_encode($baru));

        return back()->withFragment('beranda')->with('status', 'Background pembuka diperbarui.');
    }
}
