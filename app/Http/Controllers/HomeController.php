<?php

namespace App\Http\Controllers;

use App\Services\HomeService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function finances(Request $request, HomeService $homeService)
    {
        $anoSelecionado = $request->integer('year', (int) date('Y'));
        $data = $homeService->index($anoSelecionado);

        return view('finances.index', compact('data', 'anoSelecionado'));
    }
}
