<?php

namespace App\Services;

use App\Models\PluggyTransaction;
use App\Support\Money;

class FinancialSummaryService
{
    /** @return array{income: float, expenses: float, pix_expenses: float} */
    public function importedMonthTotals(int $userId, int $year, int $month): array
    {
        $base = PluggyTransaction::query()->where('user_id', $userId)->forMonth($year, $month)->contributesToTotals();
        $totals = ['income' => 0, 'expenses' => 0, 'pix_expenses' => 0];
        foreach ($base->get(['amount', 'classification', 'payment_method']) as $item) {
            $cents = Money::cents($item->amount);
            if ($item->classification === PluggyTransaction::CLASSIFICATION_INCOME) {
                $totals['income'] += $cents;
            } elseif (in_array($item->classification, [PluggyTransaction::CLASSIFICATION_EXPENSE, PluggyTransaction::CLASSIFICATION_FEE, PluggyTransaction::CLASSIFICATION_REFUND, PluggyTransaction::CLASSIFICATION_CASHBACK], true)) {
                $reversal = in_array($item->classification, [PluggyTransaction::CLASSIFICATION_REFUND, PluggyTransaction::CLASSIFICATION_CASHBACK], true);
                $totals[$item->payment_method === 'pix' ? 'pix_expenses' : 'expenses'] += $reversal ? -$cents : $cents;
            }
        }
        return array_map(fn ($cents) => (float) Money::decimal($cents), $totals);
    }

    /** @return array<int, float> */
    public function importedExpensesByMonth(int $userId, int $year): array
    {
        $totals = array_fill(1, 12, 0);
        $transactions = PluggyTransaction::query()
            ->where('user_id', $userId)
            ->whereYear('transaction_date', $year)
            ->contributesToTotals()
            ->whereIn('classification', [
                PluggyTransaction::CLASSIFICATION_EXPENSE,
                PluggyTransaction::CLASSIFICATION_FEE,
                PluggyTransaction::CLASSIFICATION_REFUND,
                PluggyTransaction::CLASSIFICATION_CASHBACK,
            ])
            ->get(['transaction_date', 'amount', 'classification']);

        foreach ($transactions as $transaction) {
            $multiplier = in_array($transaction->classification, [PluggyTransaction::CLASSIFICATION_REFUND, PluggyTransaction::CLASSIFICATION_CASHBACK], true) ? -1 : 1;
            $totals[$transaction->transaction_date->month] += $multiplier * Money::cents($transaction->amount);
        }

        return array_map(fn ($cents) => (float) Money::decimal($cents), $totals);
    }

    /** @return array<int, float> */
    public function importedIncomeByMonth(int $userId, int $year): array
    {
        $totals = array_fill(1, 12, 0);
        $transactions = PluggyTransaction::query()
            ->where('user_id', $userId)
            ->whereYear('transaction_date', $year)
            ->contributesToTotals()
            ->economicIncome()
            ->get(['transaction_date', 'amount']);

        foreach ($transactions as $transaction) {
            $totals[$transaction->transaction_date->month] += Money::cents($transaction->amount);
        }

        return array_map(fn ($cents) => (float) Money::decimal($cents), $totals);
    }
}
