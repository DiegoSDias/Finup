<?php

namespace App\Services;

use App\Models\AccountPayable;
use App\Models\Expense;
use App\Models\ManualBank;
use App\Models\PayableSettlement;
use App\Models\PluggyTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayableSettlementService
{
    public function __construct(private readonly MonthlyBalanceService $balances) {}

    public function settleManually(AccountPayable $payable, int $userId, array $data): void
    {
        DB::transaction(function () use ($payable, $userId, $data): void {
            $payable = $this->lockPendingPayable($payable, $userId);
            $date = Carbon::parse($data['payment_date'])->startOfDay();
            [$bankId, $bankStatus] = $this->resolveBank($userId, $data);

            $expense = Expense::create([
                'user_id' => $userId,
                'category_id' => $payable->category_id,
                'date' => $date->toDateString(),
                'month' => $date->month,
                'year' => $date->year,
                'payment_mode' => $data['payment_mode'],
                'manual_bank_id' => $bankId,
                'bank_status' => $bankStatus,
                'type' => 'expense',
                'description' => $payable->description,
                'amount' => $payable->amount,
                'is_installment' => false,
            ]);

            PayableSettlement::create([
                'account_payable_id' => $payable->id,
                'user_id' => $userId,
                'manual_expense_id' => $expense->id,
                'paid_on' => $date->toDateString(),
            ]);
            $payable->update(['status' => AccountPayable::STATUS_PAID, 'paid_at' => now()]);
            $this->balances->recalculateClosedFrom($userId, $date->year, $date->month);
        });
    }

    public function settleWithPluggy(AccountPayable $payable, int $userId, int $transactionId): void
    {
        DB::transaction(function () use ($payable, $userId, $transactionId): void {
            $payable = $this->lockPendingPayable($payable, $userId);
            $transaction = PluggyTransaction::query()->where('user_id', $userId)
                ->whereNull('removed_at')->whereNull('deleted_by_user_at')->contributesToTotals()
                ->whereIn('classification', [PluggyTransaction::CLASSIFICATION_EXPENSE, PluggyTransaction::CLASSIFICATION_FEE])
                ->lockForUpdate()->find($transactionId);

            if (! $transaction || PayableSettlement::query()->where('pluggy_transaction_id', $transaction->id)->exists()) {
                throw ValidationException::withMessages(['pluggy_transaction_id' => 'Escolha uma transação importada disponível para vincular.']);
            }

            PayableSettlement::create([
                'account_payable_id' => $payable->id,
                'user_id' => $userId,
                'pluggy_transaction_id' => $transaction->id,
                'paid_on' => $transaction->transaction_date->toDateString(),
            ]);
            $payable->update(['status' => AccountPayable::STATUS_PAID, 'paid_at' => now()]);
        });
    }

    public function reopen(AccountPayable $payable, int $userId): void
    {
        DB::transaction(function () use ($payable, $userId): void {
            $payable = AccountPayable::query()->forUser($userId)->lockForUpdate()->findOrFail($payable->id);
            $settlement = $payable->settlement()->lockForUpdate()->firstOrFail();
            $expense = $settlement->manualExpense;
            $settlement->delete();
            $payable->update(['status' => AccountPayable::STATUS_PENDING, 'paid_at' => null]);

            if ($expense) {
                $date = $expense->date;
                $expense->delete();
                $this->balances->recalculateClosedFrom($userId, $date->year, $date->month);
            }
        });
    }

    private function lockPendingPayable(AccountPayable $payable, int $userId): AccountPayable
    {
        $payable = AccountPayable::query()->forUser($userId)->lockForUpdate()->findOrFail($payable->id);
        if (! $payable->isPending() || $payable->settlement()->exists() || ! $payable->category_id) {
            throw ValidationException::withMessages(['payable' => 'Esta conta não está disponível para pagamento.']);
        }

        return $payable;
    }

    /** @return array{?int, ?string} */
    private function resolveBank(int $userId, array $data): array
    {
        if ($data['bank_choice'] === 'none') {
            return [null, 'none'];
        }
        if ($data['bank_choice'] === 'new') {
            return [ManualBank::firstOrCreate(['user_id' => $userId, 'name' => trim($data['new_bank'])])->id, 'bank'];
        }

        return [(int) $data['bank_choice'], 'bank'];
    }
}
