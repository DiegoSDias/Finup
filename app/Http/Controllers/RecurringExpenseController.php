<?php

namespace App\Http\Controllers;

use App\Http\Requests\RecurringExpenseRequest;
use App\Models\ManualBank;
use App\Models\RecurringExpense;
use App\Services\RecurringExpenseService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RecurringExpenseController extends Controller
{
    public function index(Request $request)
    {
        $recurrences = $request->user()->recurringExpenses()
            ->with(['category', 'manualBank'])
            ->latest('is_active')
            ->latest('start_date')
            ->get();

        return view('recurring-expenses.index', compact('recurrences'));
    }

    public function create(Request $request)
    {
        return view('recurring-expenses.create', ['categories' => $request->user()->categories()->where('type', 'expense')->orderBy('name')->get(),
            'banks' => $request->user()->manualBanks()->orderBy('name')->get()]);
    }

    public function edit(Request $request, RecurringExpense $recurringExpense)
    {
        $this->authorizeOwner($request, $recurringExpense);

        return view('recurring-expenses.edit', [
            'recurringExpense' => $recurringExpense,
            'categories' => $request->user()->categories()->where('type', 'expense')->orderBy('name')->get(),
            'banks' => $request->user()->manualBanks()->orderBy('name')->get(),
        ]);
    }

    public function store(RecurringExpenseRequest $request, RecurringExpenseService $service): RedirectResponse
    {
        $data = $this->values($request);
        $series = $service->create($request->user()->id, $data);
        return redirect()->route('month.show', [$series->start_date->year, $series->start_date->month])->with('success', 'Despesa recorrente criada. As próximas ocorrências foram geradas.');
    }

    public function update(RecurringExpenseRequest $request, RecurringExpense $recurringExpense, RecurringExpenseService $service): RedirectResponse
    {
        $this->authorizeOwner($request, $recurringExpense);
        $service->updateSeries($recurringExpense, $this->values($request));

        return redirect()->route('recurring-expenses.index')->with('success', 'Recorrência atualizada para as próximas ocorrências.');
    }

    public function end(Request $request, RecurringExpense $recurringExpense, RecurringExpenseService $service): RedirectResponse
    {
        $this->authorizeOwner($request, $recurringExpense);
        abort_unless($recurringExpense->is_active, 404);

        $data = $request->validate([
            'from_date' => ['required', 'date', function (string $attribute, mixed $value, \Closure $fail) use ($recurringExpense): void {
                try {
                    $from = Carbon::parse($value)->startOfDay();
                } catch (\Throwable) {
                    return;
                }

                if ($from->lessThan($recurringExpense->start_date->startOfDay())) {
                    $fail('A data de encerramento não pode ser anterior à primeira ocorrência.');
                }
            }],
            'remove_future' => ['nullable', 'boolean'],
        ]);
        $from = Carbon::parse($data['from_date'])->startOfDay();
        $service->end($recurringExpense, $from, (bool) ($data['remove_future'] ?? false));
        return redirect()->route('month.show', [$from->year, $from->month])->with('success', 'Recorrência encerrada.');
    }

    public function destroy(Request $request, RecurringExpense $recurringExpense, RecurringExpenseService $service): RedirectResponse
    {
        $this->authorizeOwner($request, $recurringExpense);
        $service->deleteSeries($recurringExpense);

        return redirect()->route('recurring-expenses.index')->with('success', 'Recorrência excluída. O histórico das despesas foi preservado.');
    }

    private function values(RecurringExpenseRequest $request): array
    {
        $data = $request->validated();
        $choice = $data['bank_choice'];
        $bank = $choice === 'new' ? ManualBank::firstOrCreate(['user_id' => $request->user()->id, 'name' => trim($data['new_bank'])]) : null;
        return [
            'category_id' => $data['category_id'], 
            'manual_bank_id' => $choice === 'none' ? null : ($bank?->id ?? (int) $choice),
            'bank_status' => $choice === 'none' ? 'none' : 'bank', 
            'payment_mode' => $data['payment_mode'], 
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'], 
            'frequency' => $data['frequency'], 
            'start_date' => $data['date'], 
            'end_date' => $data['end_date'] ?? null
        ];
    }

    private function authorizeOwner(Request $request, RecurringExpense $recurringExpense): void
    {
        abort_unless($recurringExpense->user_id === $request->user()->id, 404);
    }
}
