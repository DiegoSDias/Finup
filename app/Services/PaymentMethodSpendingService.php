<?php

namespace App\Services;

use App\Support\Money;

class PaymentMethodSpendingService
{
    private const LABELS = [
        'pix' => 'Pix', 'credito' => 'Crédito', 'debito' => 'Débito', 'dinheiro' => 'Dinheiro',
        'card' => 'Cartão sem modalidade', 'other' => 'Outros / não informado',
    ];

    public function summarizeMovements(iterable $movements): array
    {
        $totals = [];
        foreach ($movements as $movement) {
            if ($movement['type'] !== 'expense') {
                continue;
            }
            $method = match ($movement['payment_method']) {
                'pix', 'credito', 'debito', 'dinheiro' => $movement['payment_method'],
                'cartao', 'card' => 'card',
                default => 'other',
            };
            $totals[$method] = ($totals[$method] ?? 0) + $movement['cents'];
        }

        arsort($totals, SORT_NUMERIC);
        $positiveTotal = array_sum(array_map(fn (int $cents) => max(0, $cents), $totals));
        $maxAmount = max([1, ...array_map('abs', array_values($totals))]);
        $rows = [];
        foreach ($totals as $method => $cents) {
            $rows[] = [
                'method' => $method, 'label' => self::LABELS[$method],
                'amount' => Money::decimal($cents), 'cents' => $cents,
                'percentage' => $cents < 0 ? null : ($positiveTotal > 0 ? round($cents * 100 / $positiveTotal, 1) : 0),
                'width' => round(abs($cents) * 100 / $maxAmount, 4),
            ];
        }

        return [
            'rows' => $rows, 'total' => Money::decimal(array_sum($totals)),
            'has_credits' => count(array_filter($totals, fn (int $cents) => $cents < 0)) > 0,
        ];
    }
}
