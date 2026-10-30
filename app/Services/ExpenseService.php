<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Expense;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Support\Money;
use Illuminate\Validation\ValidationException;
use App\Models\ManualBank;

class ExpenseService
{
    public function __construct(private readonly MonthlyBalanceService $balances) {}

    public function create(array $data): void
    {
        DB::transaction(function () use ($data) {

            $categoryId = $this->resolveCategory($data);
            $data['category_id'] = $categoryId;
            $data = $this->resolveBank($data);

            if (!empty($data['is_installment'])) {
                $this->createInstallments($data);
            } else {
                $this->createSingleExpense($data);
            }

            $date = Carbon::parse($data['date']);
            $this->balances->recalculateClosedFrom(Auth::id(), $date->year, $date->month);

        });

    }

    public function show(string $id) {
        $data = Expense::with(['category' => fn ($query) => $query->where('user_id', Auth::id())])
            ->where('user_id', Auth::id())->findOrFail($id);
        return $data;
    }

    private function resolveCategory(array $data) {
        if($data['category_id'] !== 'new') {
            return $data['category_id'];
        }

        $category = Category::create([
            'user_id' => Auth::id(),
            'name' => $data['new_category'],
            'type' => $data['type']
        ]);

        return $category->id;

    }

    private function createSingleExpense(array $data) {

        Expense::create([
                'user_id' => Auth::id(),
                'category_id' => $data['category_id'],
                'date' => $data['date'],
                'month' => $data['month'],
                'year' => $data['year'],
                'payment_mode' => $data['payment_mode'] ?? null,
                'manual_bank_id' => $data['manual_bank_id'], 'bank_status' => $data['bank_status'],
                'type' => $data['type'],
                'description' => $data['description'] ?? null,
                'amount' => $data['amount'],
                'is_installment' => false,
            ]);
    }

    private function createInstallments(array $data) {
        $installmentsGroup = Str::uuid();
        $totalInstallments = (int) $data['total_installments'];
        $cents = Money::cents($data['amount']);
        if ($cents < $totalInstallments) {
            throw ValidationException::withMessages(['amount' => 'O valor deve permitir pelo menos um centavo por parcela.']);
        }
        $base = intdiv($cents, $totalInstallments);
        $remainder = $cents % $totalInstallments;
        $baseDate = Carbon::parse($data['date'])->startOfDay();
        
        
        for($i = 1; $i <= $totalInstallments; $i++) {
            $date = $baseDate->copy()->addMonthsNoOverflow($i - 1);

            Expense::create([
                'user_id' => Auth::id(),
                'category_id' => $data['category_id'],
                'date' => $date->toDateString(),
                'month' => $date->month,
                'year' => $date->year,
                'payment_mode' => $data['payment_mode'] ?? null,
                'manual_bank_id' => $data['manual_bank_id'], 'bank_status' => $data['bank_status'],
                'type' => $data['type'],
                'description' => $data['description'] ?? null,
                'amount' => Money::decimal($base + ($i <= $remainder ? 1 : 0)),
                'is_installment' => true,
                'current_installment' => $i,
                'total_installments' => $data['total_installments'],
                'installments_group' => $installmentsGroup,
            ]);
        }
    }


    public function update(Expense $expense, array $data)
    {
        DB::transaction(function () use ($expense, $data) {

            if ($expense->recurring_expense_id) {
                $data['category_id'] = $this->resolveCategory($data);
                $data = $this->resolveBank($data);
                app(RecurringExpenseService::class)->updateOccurrence($expense, $data, $data['recurrence_scope'] ?? 'single');
                return;
            }

            $first = $expense->installments_group && ($data['installment_scope'] ?? 'group') === 'group'
                ? Expense::where('user_id', Auth::id())->where('installments_group', $expense->installments_group)->orderBy('year')->orderBy('month')->first()
                : $expense;
            $oldMonth = Carbon::create($first->year, $first->month, 1);
            $newMonth = Carbon::parse($data['date'])->startOfMonth();
            $from = $oldMonth->lessThan($newMonth) ? $oldMonth : $newMonth;
            $data['category_id'] = $this->resolveCategory($data);

            if (! ($expense->installments_group && ($data['installment_scope'] ?? 'group') === 'single')) {
                $data = $this->resolveBank($data);
            }

            if ($expense->installments_group && ($data['installment_scope'] ?? 'group') === 'single') {
                $date = Carbon::parse($data['date']);
                $expense->update([
                    'category_id' => $data['category_id'], 'payment_mode' => $data['payment_mode'] ?? null,
                    'type' => $data['type'], 'date' => $date->toDateString(), 'month' => $date->month,
                    'year' => $date->year, 'description' => $data['description'] ?? null, 'amount' => $data['amount'],
                ]);
            } elseif ($expense->installments_group || !empty($data['is_installment'])) {
                if ($expense->installments_group) {
                    Expense::where('user_id', Auth::id())->where('installments_group', $expense->installments_group)->delete();
                } else {
                    $expense->delete();
                }
                $data['month'] = $newMonth->month;
                $data['year'] = $newMonth->year;
                if (!empty($data['is_installment'])) {
                    $this->createInstallments($data);
                } else {
                    $this->createSingleExpense($data);
                }
            } else {
                $this->updateSingleExpense($expense, $data);
            }
            $this->balances->recalculateClosedFrom(Auth::id(), $from->year, $from->month);
        });
    }

    private function updateSingleExpense(Expense $expense, array $data) {

        $date = Carbon::parse($data['date']);

        $expense->update([
                'category_id' => $data['category_id'],
                'payment_mode' => $data['payment_mode'] ?? null,
                'manual_bank_id' => $data['manual_bank_id'], 'bank_status' => $data['bank_status'],
                'type' => $data['type'],
                'date' => $date->toDateString(),
                'year' => $date->year,
                'month' => $date->month,
                'description' => $data['description'] ?? null,
                'amount' => $data['amount'],
            ]);

    }

    private function resolveBank(array $data): array
    {
        $choice = $data['bank_choice'];
        $data['bank_status'] = $choice === 'unknown' ? null : ($choice === 'none' ? 'none' : 'bank');
        $data['manual_bank_id'] = match ($choice) {
            'unknown', 'none' => null,
            'new' => ManualBank::firstOrCreate(['user_id' => Auth::id(), 'name' => trim($data['new_bank'])])->id,
            default => (int) $choice,
        };
        return $data;
    }

    public function destroy(Expense $expense, string $scope = 'group') {
        DB::transaction(function() use($expense, $scope) {
            if ($expense->recurring_expense_id) {
                app(RecurringExpenseService::class)->skipOccurrence($expense);
                return;
            }
            $first = !empty($expense->installments_group) && $scope === 'group'
                ? Expense::where('user_id', Auth::id())->where('installments_group', $expense->installments_group)->orderBy('year')->orderBy('month')->first()
                : $expense;
            if(!empty($expense->installments_group) && $scope === 'group') {
                Expense::where('user_id', Auth::id())->where('installments_group', $expense->installments_group)->delete();
            } else {
                $expense->delete();
            }
            $this->balances->recalculateClosedFrom(Auth::id(), $first->year, $first->month);
        });
    }
}
