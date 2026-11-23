<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\RecurringExpense;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecurringExpenseService
{
    public function __construct(private readonly MonthlyBalanceService $balances) {}

    /** @param array<string, mixed> $data */
    public function create(int $userId, array $data): RecurringExpense
    {
        return DB::transaction(function () use ($userId, $data) {
            $series = RecurringExpense::create([...$data, 'user_id' => $userId, 'is_active' => true]);
            $this->ensureOccurrences($series, Carbon::parse($data['start_date']), $this->horizon());
            $this->balances->recalculateClosedFrom($userId, Carbon::parse($data['start_date'])->year, Carbon::parse($data['start_date'])->month);
            return $series;
        });
    }

    public function ensureMonth(int $userId, int $year, int $month): void
    {
        $from = Carbon::create($year, $month, 1)->startOfDay();
        $to = $from->copy()->endOfMonth();
        RecurringExpense::query()->where('user_id', $userId)->where('is_active', true)->orderBy('id')->each(
            fn (RecurringExpense $series) => $this->ensureOccurrences($series, $from, $to)
        );
    }

    public function ensureYear(int $userId, int $year): void
    {
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = $from->copy()->endOfYear();
        RecurringExpense::query()->where('user_id', $userId)->where('is_active', true)->orderBy('id')->each(
            fn (RecurringExpense $series) => $this->ensureOccurrences($series, $from, $to)
        );
    }

    public function updateOccurrence(Expense $expense, array $data, string $scope): void
    {
        $series = $expense->recurringExpense;
        if (! $series) {
            return;
        }

        if ($scope === 'future' && ! $series->is_active) {
            throw ValidationException::withMessages([
                'recurrence_scope' => 'Esta recorrência já foi encerrada. Você ainda pode editar somente esta ocorrência.',
            ]);
        }

        $originalDate = Carbon::parse($expense->recurrence_occurrence_date ?? $expense->date)->startOfDay();
        DB::transaction(function () use ($expense, $series, $data, $scope, $originalDate) {
            if ($scope === 'single') {
                $this->applyOccurrence($expense, $data, true);
            } else {
                // A âncora de calendário da série não muda nesta operação. Isso preserva,
                // por exemplo, o retorno ao dia 31 após fevereiro. As exceções individuais
                // continuam vinculadas à data originalmente programada.
                $series->update($this->seriesValues($data));
                Expense::query()
                    ->where('recurring_expense_id', $series->id)
                    ->where('recurrence_occurrence_date', '>=', $originalDate)
                    ->where('recurrence_is_override', false)
                    ->where('recurrence_status', 'active')
                    ->delete();
                $this->ensureOccurrences($series->fresh(), $originalDate, $this->horizon());
            }
            $this->balances->recalculateClosedFrom($series->user_id, $originalDate->year, $originalDate->month);
        });
    }

    /** @param array<string, mixed> $data */
    public function updateSeries(RecurringExpense $series, array $data): void
    {
        if (! $series->is_active) {
            throw ValidationException::withMessages([
                'recurrence' => 'Uma recorrência encerrada não pode ser alterada. Crie uma nova recorrência se necessário.',
            ]);
        }

        $today = now()->startOfDay();
        $newStartDate = Carbon::parse($data['start_date'])->startOfDay();

        DB::transaction(function () use ($series, $data, $today, $newStartDate) {
            // O histórico não é reescrito. A nova data se torna a âncora de todas as
            // ocorrências futuras e os valores atualizados valem a partir de hoje.
            $series->update([...$this->seriesValues($data), 'start_date' => $newStartDate->toDateString()]);
            Expense::query()
                ->where('recurring_expense_id', $series->id)
                ->whereDate('recurrence_occurrence_date', '>=', $today->toDateString())
                ->where('recurrence_is_override', false)
                ->where('recurrence_status', 'active')
                ->delete();
            $this->ensureOccurrences($series->fresh(), $today, $this->horizon());
            $this->balances->recalculateClosedFrom($series->user_id, $today->year, $today->month);
        });
    }

    public function skipOccurrence(Expense $expense): void
    {
        if (! $expense->recurring_expense_id) {
            return;
        }

        DB::transaction(function () use ($expense) {
            // A exclusão de uma ocorrência é uma exceção da série. Marcá-la como override
            // impede que uma edição posterior da série a gere novamente.
            $expense->update(['recurrence_status' => 'skipped', 'recurrence_is_override' => true]);
            $this->balances->recalculateClosedFrom($expense->user_id, $expense->year, $expense->month);
        });
    }

    public function end(RecurringExpense $series, Carbon $from, bool $removeFuture): void
    {
        DB::transaction(function () use ($series, $from, $removeFuture) {
            $series->update(['is_active' => false, 'ended_at' => now(), 'end_date' => $from->copy()->subDay()->toDateString()]);
            if ($removeFuture) {
                Expense::query()
                    ->where('recurring_expense_id', $series->id)
                    ->where('recurrence_occurrence_date', '>=', $from)
                    ->delete();
            }
            $this->balances->recalculateClosedFrom($series->user_id, $from->year, $from->month);
        });
    }

    public function deleteSeries(RecurringExpense $series): void
    {
        if (! $series->canBeDeleted()) {
            throw ValidationException::withMessages([
                'recurrence' => 'A recorrência só pode ser excluída quando não houver ocorrências futuras ativas.',
            ]);
        }

        DB::transaction(fn () => $series->delete());
    }

    private function ensureOccurrences(RecurringExpense $series, Carbon $from, Carbon $to): void
    {
        $start = $series->start_date->copy()->startOfDay();
        $end = $series->end_date?->copy()->endOfDay();
        $limit = $end && $end->lessThan($to) ? $end : $to;
        if ($limit->lessThan($start)) {
            return;
        }

        $months = RecurringExpense::FREQUENCIES[$series->frequency] ?? null;
        if (! $months) {
            return;
        }

        $offset = max(0, intdiv(max(0, $start->diffInMonths($from, false)), $months));
        for ($i = $offset; ; $i++) {
            // Sempre calculamos a partir da data original. Assim, 31/01 gera 28/02,
            // mas volta a 31/03 quando o dia existir novamente.
            $date = $start->copy()->addMonthsNoOverflow($months * $i);
            if ($date->greaterThan($limit)) {
                break;
            }
            if ($date->lessThan($from)) {
                continue;
            }

            // Uma edição ou exclusão individual em um mês futuro é uma exceção. Mesmo
            // quando a data-base da série muda, não criamos uma segunda cobrança naquele mês.
            $hasOverrideInSameMonth = Expense::query()
                ->where('recurring_expense_id', $series->id)
                ->where('recurrence_is_override', true)
                ->where('year', $date->year)
                ->where('month', $date->month)
                ->exists();
            if ($hasOverrideInSameMonth) {
                continue;
            }

            $exists = Expense::query()
                ->where('recurring_expense_id', $series->id)
                ->whereDate('recurrence_occurrence_date', $date->toDateString())
                ->exists();

            if (! $exists) {
                Expense::create([
                    'recurring_expense_id' => $series->id, 'recurrence_occurrence_date' => $date->toDateString(),
                    'user_id' => $series->user_id, 'category_id' => $series->category_id, 'manual_bank_id' => $series->manual_bank_id,
                    'bank_status' => $series->bank_status, 'payment_mode' => $series->payment_mode, 'type' => 'expense',
                    'description' => $series->description, 'amount' => $series->amount, 'date' => $date->toDateString(),
                    'year' => $date->year, 'month' => $date->month, 'is_installment' => false, 'recurrence_status' => 'active',
                ]);
            }
        }
    }

    /** @param array<string, mixed> $data */
    private function seriesValues(array $data): array
    {
        return collect($data)->only(['category_id', 'manual_bank_id', 'bank_status', 'payment_mode', 'description', 'amount', 'frequency', 'end_date'])->all();
    }

    /** @param array<string, mixed> $data */
    private function applyOccurrence(Expense $expense, array $data, bool $override): void
    {
        $date = Carbon::parse($data['date']);
        $expense->update([...collect($data)->only(['category_id', 'manual_bank_id', 'bank_status', 'payment_mode', 'description', 'amount'])->all(),
            'date' => $date->toDateString(), 'year' => $date->year, 'month' => $date->month, 'recurrence_is_override' => $override]);
    }

    private function horizon(): Carbon { return now()->addMonths(12)->endOfMonth(); }
}
