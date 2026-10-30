<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpensesRequest;
use App\Models\Category;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Str;
use App\Support\Money;
use Carbon\Carbon;
use App\Models\ManualBank;
use App\Services\RecurringExpenseService;

class ExpensesController extends Controller
{

    protected ExpenseService $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('expenses.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $type = $request->type;
        $year = $request->year;
        $month = $request->month;
        $categories = Category::where('user_id', Auth::id())
                    ->where('type', $type)
                    ->get();
        
        $banks = ManualBank::where('user_id', Auth::id())->orderBy('name')->get();
        return view('expenses.create', compact('type', 'year', 'month', 'categories', 'banks'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ExpensesRequest $request)
    {
        $data = $request->validated();

        $date = Carbon::parse($data['date']);
        $data['year'] = $date->year;
        $data['month'] = $date->month;

        $this->expenseService->create($data);
        
        return redirect()
            ->route('month.show', [ $data['year'], $data['month']])
            ->with('success', 'Despesa criada com sucesso!');

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $expense = $this->expenseService->show($id);

        return view('expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        
        $expense = Expense::where('user_id', Auth::id())->findOrFail($id);
        $type = $expense->type;
        $year = $expense->year;
        $month = $expense->month;
        $categories = Category::where('user_id', Auth::id())->where('type', $type)->get();
        $groupTotal = null;
        $groupDate = null;
        if ($expense->installments_group) {
            $installments = Expense::where('user_id', Auth::id())->where('installments_group', $expense->installments_group)->orderBy('current_installment')->get();
            $groupTotal = Money::decimal($installments->sum(fn ($item) => Money::cents($item->amount)));
            $groupDate = $installments->first()?->date->format('Y-m-d');
        }

        $recurringExpense = $expense->recurringExpense;

        $banks = ManualBank::where('user_id', Auth::id())->orderBy('name')->get();
        return view('expenses.edit', compact('expense', 'type', 'year', 'month', 'categories', 'groupTotal', 'groupDate', 'banks', 'recurringExpense'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExpensesRequest $request, string $id)
    {
        $data = $request->validated();

        $date = Carbon::parse($data['date']);
        $data['year'] = $date->year;
        $data['month'] = $date->month;

        $isRecurring = false;
        $recurringYear = null;
        $recurringMonth = null;
        if(!empty($data['is_installment'])) {
            $expense = Expense::where('user_id', Auth::id())->findOrFail($id);
            $data['current_installment'] = $expense->current_installment;
            if($expense->installments_group && ($data['installment_scope'] ?? 'group') === 'group') {
                $expense = Expense::where('user_id', Auth::id())->where('installments_group', $expense->installments_group)
                                    ->orderBy('current_installment')->firstOrFail();
            }
        } else {
             $expense = Expense::where('user_id', Auth::id())->findOrFail($id);
        }

        if ($expense->recurring_expense_id) {
            $isRecurring = true;
            $recurringYear = $expense->year;
            $recurringMonth = $expense->month;
        }
        
        $this->expenseService->update($expense, $data);
        
        return redirect()
            ->route('month.show', [ $isRecurring ? $recurringYear : $data['year'], $isRecurring ? $recurringMonth : $data['month']])
            ->with('success', 'Despesa atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function confirmDelete(Expense $expense)
    {
        abort_unless($expense->user_id === Auth::id(), 404);

        return view('expenses.confirm-delete', compact('expense'));
    }

    public function destroy(Request $request, Expense $expense)
    {
        abort_unless($expense->user_id === Auth::id(), 404);
        $scope = $request->validate(['installment_scope' => 'nullable|in:single,group', 'recurrence_scope' => 'nullable|in:single,future']);
        $year = $expense->year;
        $month = $expense->month;
        if ($expense->recurring_expense_id && ($scope['recurrence_scope'] ?? 'single') === 'future') {
            app(RecurringExpenseService::class)->end($expense->recurringExpense, $expense->recurrence_occurrence_date ?? $expense->date, true);
        } else {
            $this->expenseService->destroy($expense, $scope['installment_scope'] ?? 'single');
        }

        return redirect()
            ->route('month.show', [$year, $month])
            ->with('success', 'Despesa excluída com sucesso');
    }
}
