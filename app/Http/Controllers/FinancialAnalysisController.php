<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinancialAnalysisRequest;
use App\Services\FinancialAnalysisService;
use App\Services\FinancialMovementService;
use App\Services\RecurringExpenseService;

class FinancialAnalysisController extends Controller
{
    public function index(FinancialAnalysisRequest $request, FinancialAnalysisService $service, FinancialMovementService $movements, RecurringExpenseService $recurrences)
    {
        $filters = array_filter($request->validated(), fn ($value) => $value !== null && $value !== '');
        $filters['year'] = (int) ($filters['year'] ?? now()->year);
        $userId = $request->user()->id;
        if (! empty($filters['month'])) {
            $recurrences->ensureMonth($userId, $filters['year'], (int) $filters['month']);
        } else {
            $recurrences->ensureYear($userId, $filters['year']);
        }
        $analysis = $service->summarize($userId, $filters);
        $options = $movements->filterOptions($userId);

        return view('analyses.index', compact('filters', 'analysis', 'options'));
    }
}
