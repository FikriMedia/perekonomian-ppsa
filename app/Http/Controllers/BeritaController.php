<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use Illuminate\View\View;

class BeritaController extends Controller
{
    public function index(): View
    {
        $beritas = Berita::terbaru()->paginate(9);

        return view('berita.index', compact('beritas'));
    }

    public function show(Berita $berita): View
    {
        $lainnya = Berita::terbaru()->whereKeyNot($berita->getKey())->take(3)->get();

        return view('berita.show', compact('berita', 'lainnya'));
    }
}
