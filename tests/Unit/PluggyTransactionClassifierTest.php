<?php

namespace Tests\Unit;

use App\Models\PluggyTransaction;
use App\Services\Pluggy\PluggyTransactionClassifier;
use PHPUnit\Framework\TestCase;

class PluggyTransactionClassifierTest extends TestCase
{
    public function test_it_classifies_a_posted_debit_as_an_expense(): void
    {
        $result = (new PluggyTransactionClassifier())->classify([
            'type' => 'DEBIT',
            'description' => 'Supermercado',
        ], 'BANK');

        $this->assertSame(PluggyTransaction::CLASSIFICATION_EXPENSE, $result['classification']);
        $this->assertTrue($result['affects_totals']);
        $this->assertSame('other', $result['payment_method']);
    }

    public function test_it_classifies_a_credit_as_income(): void
    {
        $result = (new PluggyTransactionClassifier())->classify([
            'type' => 'CREDIT',
            'description' => 'Salário',
            'paymentData' => ['paymentMethod' => 'PIX'],
        ], 'BANK');

        $this->assertSame(PluggyTransaction::CLASSIFICATION_INCOME, $result['classification']);
        $this->assertTrue($result['affects_totals']);
        $this->assertSame('pix', $result['payment_method']);
    }

    public function test_it_does_not_count_credit_card_payment_twice(): void
    {
        $result = (new PluggyTransactionClassifier())->classify([
            'type' => 'DEBIT',
            'description' => 'Pagamento de fatura',
        ], 'BANK');

        $this->assertSame(PluggyTransaction::CLASSIFICATION_CREDIT_CARD_PAYMENT, $result['classification']);
        $this->assertFalse($result['affects_totals']);
    }

    public function test_it_treats_refunds_as_expense_reversals(): void
    {
        $result = (new PluggyTransactionClassifier())->classify([
            'type' => 'CREDIT',
            'operationType' => 'ESTORNO',
            'description' => 'Estorno da compra',
        ], 'CREDIT');

        $this->assertSame(PluggyTransaction::CLASSIFICATION_REFUND, $result['classification']);
        $this->assertTrue($result['affects_totals']);
    }

    public function test_it_treats_a_refund_named_in_the_description_as_an_expense_reversal(): void
    {
        $result = (new PluggyTransactionClassifier())->classify([
            'type' => 'CREDIT',
            'operationType' => 'PAGAMENTO',
            'description' => 'Estorno de Uber - NuPay',
        ], 'CREDIT');

        $this->assertSame(PluggyTransaction::CLASSIFICATION_REFUND, $result['classification']);
        $this->assertTrue($result['affects_totals']);
    }

    public function test_it_excludes_known_reserved_balance_transfers(): void
    {
        $result = (new PluggyTransactionClassifier())->classify([
            'type' => 'DEBIT',
            'operationType' => 'TRANSFERENCIA_SALDO_RESERVADO',
            'description' => 'Dinheiro reservado Guardar Dinheiro',
        ], 'BANK');

        $this->assertSame(PluggyTransaction::CLASSIFICATION_INTERNAL_RESERVE_TRANSFER, $result['classification']);
        $this->assertFalse($result['affects_totals']);
    }

    public function test_it_does_not_exclude_an_investment_operation_without_a_specific_transfer_signal(): void
    {
        $result = (new PluggyTransactionClassifier())->classify([
            'type' => 'DEBIT',
            'operationType' => 'INVESTIMENTO',
            'description' => 'Aplicação financeira',
        ], 'BANK');

        $this->assertSame(PluggyTransaction::CLASSIFICATION_EXPENSE, $result['classification']);
        $this->assertTrue($result['affects_totals']);
    }

    public function test_it_does_not_infer_pix_from_card_operations(): void
    {
        $result = (new PluggyTransactionClassifier())->classify([
            'type' => 'DEBIT',
            'operationType' => 'CARTAO',
            'description' => 'Compra no débito',
        ], 'BANK');

        $this->assertSame('card', $result['payment_method']);
    }

    public function test_payment_method_requires_consistent_evidence(): void
    {
        $classifier = new PluggyTransactionClassifier();
        foreach ([
            [['operationType' => 'PIX'], 'BANK', 'pix'],
            [['paymentData' => ['paymentMethod' => 'PIX']], 'BANK', 'pix'],
            [['operationType' => 'CARTAO'], 'BANK', 'card'],
            [[], 'CREDIT', 'card'],
            [['operationType' => 'CARTAO', 'paymentData' => ['paymentMethod' => 'PIX']], 'BANK', 'other'],
            [['operationType' => 'TED', 'paymentData' => ['paymentMethod' => 'PIX']], 'BANK', 'other'],
            [['operationType' => 'CARTAO', 'paymentData' => ['paymentMethod' => 'TED']], 'BANK', 'other'],
            [['description' => 'Pix não confirmado', 'operationType' => 'OUTROS'], 'BANK', 'other'],
        ] as [$payload, $account, $expected]) {
            $this->assertSame($expected, $classifier->classify($payload, $account)['payment_method']);
        }
    }
}
