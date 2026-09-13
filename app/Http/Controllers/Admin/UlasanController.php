<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ulasan;
use Illuminate\Http\RedirectResponse;

class UlasanController extends Controller
{
    public function approve(Ulasan $ulasan): RedirectResponse
    {
        $ulasan->update(['status' => Ulasan::APPROVED]);

        return back()->withFragment('kelola-ulasan')->with('status', 'Ulasan disetujui dan sekarang tampil.');
    }

    public function destroy(Ulasan $ulasan): RedirectResponse
    {
        $ulasan->delete();

        return back()->withFragment('kelola-ulasan')->with('status', 'Ulasan dihapus.');
    }
}
