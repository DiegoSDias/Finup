<?php

namespace App\Services\Pluggy;

use App\Models\PluggyTransaction;

class PluggyTransactionClassifier
{
    /** @param array<string, mixed> $transaction
     *  @return array{classification: string, affects_totals: bool, payment_method: string}
     */
    public function classify(array $transaction, string $accountType): array
    {
        $operationType = strtoupper(trim((string) ($transaction['operationType'] ?? '')));
        $direction = strtoupper((string) ($transaction['type'] ?? ''));
        $paymentMethod = strtoupper(trim((string) data_get($transaction, 'paymentData.paymentMethod', '')));
        $description = mb_strtoupper((string) ($transaction['description'] ?? ''));

        $classification = match (true) {
            $operationType === 'PAGAMENTO_FATURA' => PluggyTransaction::CLASSIFICATION_CREDIT_CARD_PAYMENT,
            $operationType === 'ESTORNO' => PluggyTransaction::CLASSIFICATION_REFUND,
            str_contains($description, 'ESTORNO') => PluggyTransaction::CLASSIFICATION_REFUND,
            $operationType === 'CASHBACK' => PluggyTransaction::CLASSIFICATION_CASHBACK,
            $operationType === 'TARIFA' => PluggyTransaction::CLASSIFICATION_FEE,
            $operationType === 'TRANSFERENCIA_SALDO_RESERVADO' => PluggyTransaction::CLASSIFICATION_INTERNAL_RESERVE_TRANSFER,
            str_contains($description, 'FATURA') &&
                (str_contains($description, 'PAGAMENTO') || str_contains($description, 'PAGTO'))
                => PluggyTransaction::CLASSIFICATION_CREDIT_CARD_PAYMENT,
            $direction === 'CREDIT' => PluggyTransaction::CLASSIFICATION_INCOME,
            $direction === 'DEBIT' => PluggyTransaction::CLASSIFICATION_EXPENSE,
            default => PluggyTransaction::CLASSIFICATION_UNKNOWN,
        };

        $affectsTotals = in_array($classification, [
            PluggyTransaction::CLASSIFICATION_INCOME,
            PluggyTransaction::CLASSIFICATION_EXPENSE,
            PluggyTransaction::CLASSIFICATION_FEE,
            PluggyTransaction::CLASSIFICATION_REFUND,
            PluggyTransaction::CLASSIFICATION_CASHBACK,
        ], true);

        return [
            'classification' => $classification,
            'affects_totals' => $affectsTotals,
            'payment_method' => $this->paymentMethod($operationType, $paymentMethod, $accountType),
        ];
    }

    private function paymentMethod(string $operationType, string $paymentMethod, string $accountType): string
    {
        $pix = $operationType === 'PIX' || $paymentMethod === 'PIX';
        $card = $operationType === 'CARTAO' || $paymentMethod === 'CARTAO';
        $nonPixTransfer = in_array($operationType, ['TED', 'DOC', 'BOLETO'], true)
            || in_array($paymentMethod, ['TED', 'DOC', 'BOLETO'], true);

        if (($pix && ($card || $nonPixTransfer)) || ($card && $nonPixTransfer)) {
            return 'other';
        }
        if ($pix) {
            return 'pix';
        }
        if ($card || (strtoupper($accountType) === 'CREDIT' && ! $nonPixTransfer)) {
            return 'card';
        }

        return 'other';
    }
}
