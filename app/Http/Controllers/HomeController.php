<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinancialAnalysisRequest;
use App\Services\HomeService;

class HomeController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    public function finances(FinancialAnalysisRequest $request, HomeService $homeService)
    {
        $anoSelecionado = (int) ($request->validated()['year'] ?? now()->year);
        $data = $homeService->index($anoSelecionado);

        return view('finances.index', compact('data', 'anoSelecionado'));
    }
}
