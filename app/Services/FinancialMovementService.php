<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Group;
use App\Models\ManualBank;
use App\Models\PluggyAccount;
use App\Models\PluggyTransaction;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FinancialMovementService
{
    /** @param array<string, mixed> $filters */
    public function get(int $userId, array $filters): Collection
    {
        $from = CarbonImmutable::create((int) $filters['year'], (int) ($filters['month'] ?? 1), 1)->startOfDay();
        $to = empty($filters['month']) ? $from->endOfYear() : $from->endOfMonth();
        $source = $filters['source'] ?? null;
        $bank = $filters['bank'] ?? '';
        $movements = collect();
        $category = ['category' => fn ($query) => $query->where('user_id', $userId)
            ->with(['group' => fn ($group) => $group->where('user_id', $userId)])];

        if ($source !== 'imported' && ! str_starts_with($bank, 'account:')) {
            $manual = Expense::where('user_id', $userId)->includedInTotals()
                ->whereIn('type', ['income', 'expense'])
                ->where('date', '>=', $from->toDateString())
                ->where('date', '<', $to->addDay()->toDateString())->with($category);
            $this->filterCategories($manual, $userId, $filters);
            if (str_starts_with($bank, 'manual:')) {
                $manual->where('manual_bank_id', substr($bank, 7));
            } elseif ($bank === 'none') {
                $manual->where('bank_status', 'none');
            } elseif ($bank === 'unknown') {
                $manual->whereNull('bank_status');
            }
            foreach ($manual->get(['id', 'category_id', 'type', 'amount', 'date', 'payment_mode', 'description']) as $expense) {
                $movements->push($this->normalize($expense, 'manual', $expense->type, Money::cents($expense->amount)));
            }
        }

        if ($source !== 'manual' && ($bank === '' || str_starts_with($bank, 'account:'))) {
            $imported = PluggyTransaction::where('user_id', $userId)->contributesToTotals()
                ->where('transaction_date', '>=', $from->toDateString())
                ->where('transaction_date', '<', $to->addDay()->toDateString())
                ->whereIn('classification', [
                    PluggyTransaction::CLASSIFICATION_INCOME, PluggyTransaction::CLASSIFICATION_EXPENSE,
                    PluggyTransaction::CLASSIFICATION_FEE, PluggyTransaction::CLASSIFICATION_REFUND,
                    PluggyTransaction::CLASSIFICATION_CASHBACK,
                ])->with($category);
            $this->filterCategories($imported, $userId, $filters);
            if (str_starts_with($bank, 'account:')) {
                $imported->where('pluggy_account_id', substr($bank, 8));
            }
            foreach ($imported->get(['id', 'category_id', 'classification', 'amount', 'transaction_date', 'payment_method', 'description']) as $transaction) {
                $income = $transaction->classification === PluggyTransaction::CLASSIFICATION_INCOME;
                $reversal = in_array($transaction->classification, [
                    PluggyTransaction::CLASSIFICATION_REFUND, PluggyTransaction::CLASSIFICATION_CASHBACK,
                ], true);
                $movements->push($this->normalize($transaction, 'imported', $income ? 'income' : 'expense',
                    ($reversal ? -1 : 1) * Money::cents($transaction->amount)));
            }
        }

        return $movements;
    }

    public function filterOptions(int $userId): array
    {
        return [
            'groups' => Group::where('user_id', $userId)->orderBy('name')->get(['id', 'name']),
            'categories' => Category::where('user_id', $userId)->orderBy('name')->get(['id', 'name', 'group_id', 'type']),
            'manualBanks' => ManualBank::where('user_id', $userId)->orderBy('name')->get(['id', 'name']),
            'accounts' => PluggyAccount::where('user_id', $userId)
                ->with(['connection' => fn ($query) => $query->where('user_id', $userId)])
                ->orderBy('name')->get(),
        ];
    }

    private function normalize(Expense|PluggyTransaction $record, string $source, string $type, int $cents): array
    {
        $category = $record->category;
        $group = $category?->group;

        return [
            'id' => $record->id, 'source' => $source, 'type' => $type, 'cents' => $cents,
            'date' => $record->date->toDateString(),
            'description' => $record->description ?: ($category?->name ?? 'Movimentação sem descrição'),
            'payment_method' => $source === 'manual' ? $record->payment_mode : $record->payment_method,
            'category_key' => $category ? 'category:'.$category->id : 'none:'.$type,
            'category_label' => $category?->name ?? 'Sem categoria',
            'category_type' => $category?->type ?? $type,
            'group_key' => $group ? 'group:'.$group->id : 'none',
            'group_label' => $group?->name ?? 'Sem grupo',
        ];
    }

    /** @param array<string, mixed> $filters */
    private function filterCategories(Builder $query, int $userId, array $filters): void
    {
        if (! empty($filters['group_id'])) {
            $group = $filters['group_id'];
            $query->where(function (Builder $query) use ($userId, $group) {
                $query->whereHas('category', function (Builder $category) use ($userId, $group) {
                    $category->where('user_id', $userId);
                    $group === 'none' ? $category->whereNull('group_id') : $category->where('group_id', $group);
                });
                if ($group === 'none') {
                    $query->orWhereNull('category_id');
                }
            });
        }
        if (! empty($filters['category_id'])) {
            $filters['category_id'] === 'none'
                ? $query->whereNull('category_id')
                : $query->where('category_id', $filters['category_id']);
        }
    }
}
