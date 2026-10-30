<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Expense;
use App\Models\PluggyTransaction;
use App\Services\MonthlyBalanceService;
use App\Services\RecurringExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Support\Money;

class MonthsController extends Controller
{
    public function __construct(
        protected MonthlyBalanceService $monthlyBalanceService,
        protected RecurringExpenseService $recurrences,
    ) {
    }

    public function show(Request $request ,String $year, String $month) {

        $this->recurrences->ensureMonth(Auth::id(), (int) $year, (int) $month);

        $expenses = Expense::where('user_id', Auth::id())
            ->where('month', $month)
            ->where('year', $year)
            ->includedInTotals()
            ->get();

        $categoriesIncome = Category::where('user_id', Auth::id())
                                ->where('type', 'income')
                                ->get();

        $categoriesExpense = Category::where('user_id', Auth::id())
                                ->where('type', 'expense')
                                ->get();

        $manualIncomes = Expense::with(['manualBank', 'category' => fn ($query) => $query->where('user_id', Auth::id())])
                                ->where('user_id', Auth::id())
                                ->where('type', 'income')
                                ->where('month', $month)
                                ->where('year', $year)
                                ->includedInTotals()
                                ->when($request->income_category, function ($q) use ($request) {
                                    $q->where('category_id', $request->income_category);
                                })
                                ->when($request->income_value, function ($q) use ($request) {
                                    $q->where('amount', $request->income_value_op, $request->income_value);
                                })
                                ->when($request->date_value, function ($q) use ($request) {
                                    $q->where('date', $request->date_value, $request->date_value);
                                })
                                ->get();

        $manualExpenses = Expense::with(['manualBank', 'category' => fn ($query) => $query->where('user_id', Auth::id())])
                                ->where('user_id', Auth::id())
                                ->where('type', 'expense')
                                ->where('month', $month)
                                ->where('year', $year)
                                ->includedInTotals()
                                ->when($request->expense_category, function($q) use ($request) {
                                    $q->where('category_id', $request->expense_category);
                                })
                                ->when($request->payment_mode, function($q) use ($request) {
                                    $q->where('payment_mode', $request->payment_mode);
                                })
                                ->when($request->is_installment !== null, function($q) use ($request) {
                                    $q->where('is_installment', $request->is_installment);
                                })
                                ->when($request->expense_value, function($q) use ($request) {
                                    $q->where('amount', $request->expense_value_op, $request->expense_value);
                                })
                                ->when($request->date_value, function ($q) use ($request) {
                                    $q->where('date', $request->date_value, $request->date_value);
                                })
                                ->orderBy('date')
                                ->get();

        $importedBase = PluggyTransaction::with(['category' => fn ($query) => $query->where('user_id', Auth::id())])
            ->where('user_id', Auth::id())
            ->forMonth((int) $year, (int) $month)
            ->whereNull('removed_at')
            ->whereNull('deleted_by_user_at');

        $importedIncomes = (clone $importedBase)
            ->contributesToTotals()
            ->economicIncome()
            ->when($request->income_category, fn ($q) => $q->where('category_id', $request->income_category))
            ->when($request->income_value, fn ($q) => $q->where('amount', $request->income_value_op, $request->income_value))
            ->when($request->date_value, fn ($q) => $q->whereDate('transaction_date', $request->date_value))
            ->get();

        $importedExpenses = (clone $importedBase)
            ->contributesToTotals()
            ->whereIn('classification', [
                PluggyTransaction::CLASSIFICATION_EXPENSE,
                PluggyTransaction::CLASSIFICATION_FEE,
                PluggyTransaction::CLASSIFICATION_REFUND,
                PluggyTransaction::CLASSIFICATION_CASHBACK,
            ])
            ->when($request->expense_category, fn ($q) => $q->where('category_id', $request->expense_category))
            ->when($request->payment_mode, function ($q) use ($request) {
                if (in_array($request->payment_mode, ['pix', 'cartao', 'debito', 'credito', 'dinheiro'], true)) {
                    $q->where('payment_method', $request->payment_mode === 'cartao' ? 'card' : $request->payment_mode);
                } else {
                    $q->whereRaw('1 = 0');
                }
            })
            ->when($request->is_installment !== null, fn ($q) => $q->where('is_installment', $request->is_installment))
            ->when($request->expense_value, fn ($q) => $q->where('amount', $request->expense_value_op, $request->expense_value))
            ->when($request->date_value, fn ($q) => $q->whereDate('transaction_date', $request->date_value))
            ->get();

        $excludedTransactions = (clone $importedBase)
            ->where('affects_totals', false)
            ->orderByDesc('transaction_date')
            ->get();

        $typeIncomes = $manualIncomes->concat($importedIncomes)->sortBy('date')->values();
        $typeExpenses = $manualExpenses->concat($importedExpenses)->sortBy('date')->values();
        
        $values = $this->monthlyBalanceService->calculate($year, $month);

        $incomesBalance = $values['incomes'];
        $expensesBalance = $values['expenses'];
        $expensesPix = $values['expensesPix'];
        $balanceAvailable = $values['balanceAvailable'];
        $balance = $values['balance'];
        $balanceMonthPrevious = $values['balanceMonthPrevious'];

        $filteredIncomesTotal = (float) Money::decimal($typeIncomes->sum(fn ($item) => Money::cents($item->amount)) + Money::cents($balanceMonthPrevious));
        $filteredExpensesTotal = (float) Money::decimal($typeExpenses->sum(fn ($expense) => Money::cents($expense instanceof PluggyTransaction ? $expense->display_amount : $expense->amount)));
        $hasMovements = $expenses->isNotEmpty() || $typeIncomes->isNotEmpty() || $typeExpenses->isNotEmpty() || $excludedTransactions->isNotEmpty();
        

        return view('months.show', compact(
                                            'expenses',
                                            'hasMovements',
                                            'categoriesIncome',
                                            'categoriesExpense',
                                            'filteredIncomesTotal',
                                            'filteredExpensesTotal',
                                            'year', 
                                            'month', 
                                            'typeIncomes', 
                                            'typeExpenses',
                                            'excludedTransactions',
                                            'incomesBalance', 
                                            'expensesBalance',
                                            'expensesPix',
                                            'balanceAvailable',
                                            'balance',
                                            'balanceMonthPrevious'
                                            ));
    }

    public function close(int $year, int $month) {
        $this->recurrences->ensureMonth(Auth::id(), $year, $month);
        $this->monthlyBalanceService->closeMonth($year, $month);

        return back()->with('success', 'Mês fechado com sucesso.');
    }
}
