<?php

namespace App\Services;

use App\Support\Months;
use Carbon\CarbonImmutable;

class FinancialAnalysisService
{
    public function __construct(
        private readonly FinancialMovementService $movements,
        private readonly PaymentMethodSpendingService $payments,
    ) {}

    /** @param array<string, mixed> $filters */
    public function summarize(int $userId, array $filters): array
    {
        $movements = $this->movements->get($userId, $filters);
        $month = $filters['month'] ?? null;
        $date = CarbonImmutable::create((int) $filters['year'], (int) ($month ?? 1), 1);
        $count = $month ? $date->daysInMonth : 12;
        $periods = [];
        foreach (range(1, $count) as $number) {
            $periods[$number] = [
                'label' => $month ? str_pad((string) $number, 2, '0', STR_PAD_LEFT) : mb_substr(Months::MONTHS[$number - 1]['nome'], 0, 3),
                'full_label' => $month ? $date->day($number)->format('d/m/Y') : Months::MONTHS[$number - 1]['nome'].' de '.$filters['year'],
                'income' => 0, 'expense' => 0,
            ];
        }

        $totals = ['income' => 0, 'expense' => 0, 'balance' => 0];
        $groups = [];
        $categories = [];
        foreach ($movements as $movement) {
            $type = $movement['type'];
            $totals[$type] += $movement['cents'];
            $number = (int) substr($movement['date'], $month ? 8 : 5, 2);
            $periods[$number][$type] += $movement['cents'];

            $group = $movement['group_key'];
            $groups[$group] ??= ['key' => $group, 'label' => $movement['group_label'], 'income' => 0, 'expense' => 0];
            $groups[$group][$type] += $movement['cents'];
            $category = $movement['category_key'];
            $categories[$category] ??= [
                'key' => $category, 'label' => $movement['category_label'], 'income' => 0, 'expense' => 0,
                'category_type' => $movement['category_type'],
            ];
            $categories[$category][$type] += $movement['cents'];
        }

        $running = 0;
        foreach ($periods as &$period) {
            $running += $period['income'] - $period['expense'];
            $period['balance'] = $running;
        }
        unset($period);
        $totals['balance'] = $totals['income'] - $totals['expense'];

        return [
            'totals' => $totals,
            'periods' => array_values($periods),
            'groups' => $this->rank($groups),
            'categories' => $this->rank($categories),
            'largest' => $movements->filter(fn ($movement) => $movement['type'] === 'expense' && $movement['cents'] > 0)
                ->sort(fn ($a, $b) => ($b['cents'] <=> $a['cents']) ?: strcmp($b['date'], $a['date']) ?: strcmp($a['source'].':'.$a['id'], $b['source'].':'.$b['id']))
                ->take(10)->values()->all(),
            'payments' => $this->payments->summarizeMovements($movements),
            'has_movements' => $movements->isNotEmpty(),
            'period_label' => $month ? Months::MONTHS[(int) $month - 1]['nome'].' de '.$filters['year'] : 'Ano inteiro de '.$filters['year'],
        ];
    }

    private function rank(array $rows): array
    {
        usort($rows, fn ($a, $b) => (($b['income'] + abs($b['expense'])) <=> ($a['income'] + abs($a['expense']))) ?: strcmp($a['label'], $b['label']));

        return $rows;
    }
}
