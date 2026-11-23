<?php

namespace App\Services\Pluggy;

use App\Models\Expense;
use App\Models\PluggyTransaction;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class InstallmentReviewService
{
    /** Obtém os avisos do mês em lote, sem consultar o banco para cada linha. */
    public function reviewIds($transactions): array
    {
        $eligible = $transactions->filter(fn ($item) => $item->is_installment && $item->purchase_date && $item->current_installment && $item->total_installments);
        if ($eligible->isEmpty()) return [];

        $userId = $eligible->first()->user_id;
        $dates = $eligible->map(fn ($item) => $item->purchase_date->toDateString())->unique()->values();
        $accountIds = $eligible->pluck('pluggy_account_id')->unique()->values();
        $imported = PluggyTransaction::query()->where('user_id', $userId)->whereIn('pluggy_account_id', $accountIds)
            ->whereIn(DB::raw('DATE(purchase_date)'), $dates)->whereNull('removed_at')->whereNull('deleted_by_user_at')->get();
        $manual = Expense::query()->where('user_id', $userId)->whereNotNull('pluggy_installment_source_id')
            ->with(['pluggyInstallmentSource' => fn ($query) => $query->where('user_id', $userId)])
            ->whereHas('pluggyInstallmentSource', fn ($query) => $query->where('user_id', $userId)->whereIn('pluggy_account_id', $accountIds)->whereIn(DB::raw('DATE(purchase_date)'), $dates))->get();

        return $eligible->filter(function ($item) use ($imported, $manual) {
            $same = fn ($other) => $other->pluggy_account_id === $item->pluggy_account_id
                && $other->purchase_date?->toDateString() === $item->purchase_date->toDateString()
                && $other->current_installment === $item->current_installment && $other->total_installments === $item->total_installments
                && (string) $other->amount === (string) $item->amount;
            if ($imported->contains(fn ($other) => $other->id !== $item->id && $same($other) && $this->descriptionsMatch($item->description, $other->description))) return true;
            return $manual->contains(fn ($expense) => $expense->pluggyInstallmentSource
                && $expense->pluggyInstallmentSource->pluggy_account_id === $item->pluggy_account_id
                && $expense->pluggyInstallmentSource->purchase_date?->toDateString() === $item->purchase_date->toDateString()
                && $expense->current_installment === $item->current_installment && $expense->total_installments === $item->total_installments
                && (string) $expense->amount === (string) $item->amount);
        })->pluck('id')->all();
    }

    public function candidates(PluggyTransaction $transaction)
    {
        if (! $transaction->purchase_date || ! $transaction->current_installment || ! $transaction->total_installments) {
            return collect();
        }

        return PluggyTransaction::query()->where('user_id', $transaction->user_id)
            ->where('pluggy_account_id', $transaction->pluggy_account_id)->whereKeyNot($transaction->id)
            ->whereDate('purchase_date', $transaction->purchase_date)->where('current_installment', $transaction->current_installment)
            ->where('total_installments', $transaction->total_installments)->where('amount', $transaction->amount)
            ->whereNull('removed_at')->whereNull('deleted_by_user_at')->get()
            ->filter(function ($candidate) use ($transaction) {
                return $this->descriptionsMatch($transaction->description, $candidate->description);
            })->values();
    }

    private function descriptionsMatch(string $first, string $second): bool
    {
        $first = Str::upper(Str::ascii($first));
        $second = Str::upper(Str::ascii($second));
        return $first === $second || (mb_strlen($first) >= 8 && mb_strlen($second) >= 8
            && (str_starts_with($first, $second) || str_starts_with($second, $first)));
    }

    public function manualCandidates(PluggyTransaction $transaction)
    {
        if (! $transaction->purchase_date || ! $transaction->current_installment || ! $transaction->total_installments) {
            return collect();
        }

        return Expense::query()->where('user_id', $transaction->user_id)
            ->where('current_installment', $transaction->current_installment)
            ->where('total_installments', $transaction->total_installments)
            ->where('amount', $transaction->amount)
            ->whereHas('pluggyInstallmentSource', fn ($query) => $query->where('user_id', $transaction->user_id)
                ->where('pluggy_account_id', $transaction->pluggy_account_id)
                ->whereDate('purchase_date', $transaction->purchase_date))
            ->get();
    }
}
