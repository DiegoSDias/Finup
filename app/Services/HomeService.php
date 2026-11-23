<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\MonthlyBalance;
use App\Support\Months;
use Illuminate\Support\Facades\Auth;
use App\Support\Money;

class HomeService
{
    public function __construct(private readonly FinancialSummaryService $financialSummary)
    {
    }

    public function index(int $anoSelecionado)
    {
        app(RecurringExpenseService::class)->ensureYear(Auth::id(), $anoSelecionado);
        $meses = Months::MONTHS;

        $incomeBalance = Expense::FromUser(Auth::id())
            ->income()
            ->includedInTotals()
            ->year($anoSelecionado)
            ->get(['month', 'amount'])->groupBy('month')
            ->map(fn ($items) => $items->sum(fn ($item) => Money::cents($item->amount)));

        $closingBalances = MonthlyBalance::FromUser(Auth::id())
            ->year($anoSelecionado)
            ->pluck('closing_balance', 'month');
        $decemberBalance = MonthlyBalance::FromUser(Auth::id())
            ->year($anoSelecionado - 1)->where('month', 12)->value('closing_balance') ?? 0;

        $importedIncomeBalance = $this->financialSummary->importedIncomeByMonth(Auth::id(), $anoSelecionado);
        $finalIncomeBalance = [];

        foreach (range(1, 12) as $mes) {
            $receitaMes = ($incomeBalance[$mes] ?? 0) + Money::cents($importedIncomeBalance[$mes]);
            $saldoAnterior = Money::cents($mes === 1 ? $decemberBalance : ($closingBalances[$mes - 1] ?? 0));
            $finalIncomeBalance[$mes] = (float) Money::decimal($receitaMes + $saldoAnterior);
        }

        $expenseBalance = Expense::FromUser(Auth::id())
            ->expense()
            ->includedInTotals()
            ->year($anoSelecionado)
            ->get(['month', 'amount'])->groupBy('month')
            ->map(fn ($items) => $items->sum(fn ($item) => Money::cents($item->amount)));

        $importedExpenseBalance = $this->financialSummary->importedExpensesByMonth(Auth::id(), $anoSelecionado);
        foreach (range(1, 12) as $mes) {
            $expenseBalance[$mes] = (float) Money::decimal(($expenseBalance[$mes] ?? 0) + Money::cents($importedExpenseBalance[$mes]));
        }

        $totalExpense = (float) Money::decimal($expenseBalance->sum(fn ($amount) => Money::cents($amount)));

        $mediaAnual = $expenseBalance->count()
            ? $totalExpense / $expenseBalance->count()
            : 0;

        $maiorGasto = $expenseBalance->max();

        return [
            'meses' => $meses,
            'finalIncomeBalance' => $finalIncomeBalance,
            'expenseBalance' => $expenseBalance,
            'totalExpense' => $totalExpense,
            'mediaAnual' => $mediaAnual,
            'maiorGasto' => $maiorGasto
        ];
    }
}
