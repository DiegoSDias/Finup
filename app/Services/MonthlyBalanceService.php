<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\MonthlyBalance;
use Illuminate\Support\Facades\Auth;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class MonthlyBalanceService
{
    public function __construct(private readonly FinancialSummaryService $financialSummary)
    {
    }
    /**
     * Create a new class instance.
     */
    public function calculate(int $year, int $month, ?int $userId = null): array
    {
        $userId ??= Auth::id();
        $incomes = Expense::where('user_id', $userId)
            ->where('type', 'income')
            ->includedInTotals()
            ->where('year', $year)
            ->where('month', $month)
            ->get(['amount'])->sum(fn ($item) => Money::cents($item->amount));

        if ($month == 1) {
            $previousMonthNumber = 12;
            $previousYear = $year - 1;
        } else {
            $previousMonthNumber = $month - 1;
            $previousYear = $year;
        }

        $previousBalance = MonthlyBalance::where('user_id', $userId)
            ->where('year', $previousYear)
            ->where('month', $previousMonthNumber)
            ->first();

        $balanceMonthPrevious = Money::cents($previousBalance?->closing_balance ?? 0);
        
        $imported = $this->financialSummary->importedMonthTotals($userId, $year, $month);
        $incomes += Money::cents($imported['income']) + $balanceMonthPrevious;

        $expensesNormal = Expense::where('user_id', $userId)
            ->where('type', 'expense')
            ->includedInTotals()
            ->where('year', $year)
            ->where('month', $month)
            ->where(fn ($query) => $query->where('payment_mode', '!=', 'pix')->orWhereNull('payment_mode'))
            ->get(['amount'])->sum(fn ($item) => Money::cents($item->amount)) + Money::cents($imported['expenses']);

        $expensesPix = Expense::where('user_id', $userId)
            ->where('type', 'expense')
            ->includedInTotals()
            ->where('year', $year)
            ->where('month', $month)
            ->where('payment_mode', 'pix')
            ->get(['amount'])->sum(fn ($item) => Money::cents($item->amount)) + Money::cents($imported['pix_expenses']);

        $balanceMonth = $incomes - $expensesNormal;

        $balanceAvailable = $balanceMonth - $expensesPix;

        return [
            'incomes' => (float) Money::decimal($incomes),
            'expenses' => (float) Money::decimal($expensesNormal),
            'expensesPix' => (float) Money::decimal($expensesPix),
            'balance' => (float) Money::decimal($balanceMonth),
            'balanceAvailable' => (float) Money::decimal($balanceAvailable),
            'balanceMonthPrevious' => (float) Money::decimal($balanceMonthPrevious)
            ];
    }

    public function closeMonth(int $year, int $month): void
    {
        DB::transaction(function () use ($year, $month) {
        $balance = $this->calculate($year, $month);

        MonthlyBalance::updateOrCreate(
            [
                'user_id' => Auth::id(),
                'year' => $year,
                'month' => $month,
            ],
            [
                'closing_balance' => $balance['balanceAvailable'],
            ]
        );

        $this->recalculateClosedFrom(Auth::id(), $year, $month);
        });
    }

    public function recalculateClosedFrom(int $userId, int $year, int $month): void
    {
        $balances = MonthlyBalance::where('user_id', $userId)
            ->where(fn ($query) => $query->where('year', '>', $year)
                ->orWhere(fn ($query) => $query->where('year', $year)->where('month', '>=', $month)))
            ->orderBy('year')->orderBy('month')->get();

        foreach ($balances as $balance) {
            $value = $this->calculate($balance->year, $balance->month, $userId);
            $balance->update(['closing_balance' => $value['balanceAvailable']]);
        }
    }
}
