<?php

namespace App\Services\Pluggy;

use App\Models\PluggyAccount;
use App\Models\PluggyTransaction;
use Illuminate\Support\Collection;

class InternalTransferMatcher
{
    public function matchForUser(int $userId): int
    {
        $accounts = PluggyAccount::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        $transactions = PluggyTransaction::query()
            ->with('account')
            ->where('user_id', $userId)
            ->whereNull('removed_at')
            ->whereNull('deleted_by_user_at')
            ->where('status', 'POSTED')
            ->whereIn('direction', ['DEBIT', 'CREDIT'])
            ->whereNull('paired_transaction_id')
            ->where('is_internal_transfer', false)
            ->where(fn ($query) => $query->whereNull('exclusion_reason')->orWhereNotIn('exclusion_reason', ['user_exclusion_phrase', 'user_transfer_phrase']))
            ->orderByDesc('transaction_date')
            ->get();

        $matches = $this->markTransfersWithOwnParticipant($transactions, $accounts);
        $transactions = $transactions->reject(fn (PluggyTransaction $transaction) => $transaction->is_internal_transfer);
        $debits = $transactions->where('direction', 'DEBIT');
        $credits = $transactions->where('direction', 'CREDIT');

        foreach ($debits as $debit) {
            $credit = $credits->first(fn (PluggyTransaction $candidate) => $this->isStrongMatch($debit, $candidate));

            if (! $credit) {
                continue;
            }

            $debit->update([
                'classification' => PluggyTransaction::CLASSIFICATION_INTERNAL_TRANSFER,
                'affects_totals' => false,
                'exclusion_reason' => 'matched_internal_transfer',
                'is_internal_transfer' => true,
                'paired_transaction_id' => $credit->id,
            ]);
            $credit->update([
                'classification' => PluggyTransaction::CLASSIFICATION_INTERNAL_TRANSFER,
                'affects_totals' => false,
                'exclusion_reason' => 'matched_internal_transfer',
                'is_internal_transfer' => true,
                'paired_transaction_id' => $debit->id,
            ]);
            $credits = $credits->reject(fn (PluggyTransaction $candidate) => $candidate->is($credit));
            $matches++;
        }

        return $matches;
    }

    /** @param Collection<int, PluggyTransaction> $transactions
     *  @param Collection<int, PluggyAccount> $accounts
     */
    private function markTransfersWithOwnParticipant(Collection $transactions, Collection $accounts): int
    {
        $matches = 0;

        foreach ($transactions as $transaction) {
            $payment = $transaction->payment_data ?? [];
            $participant = $transaction->direction === 'CREDIT'
                ? ($payment['payer'] ?? [])
                : ($payment['receiver'] ?? []);

            if (! $this->isTransfer($payment, $payment)
                || ! $this->participantMatchesAnyAccount($participant, $accounts)) {
                continue;
            }

            $transaction->update([
                'classification' => PluggyTransaction::CLASSIFICATION_INTERNAL_TRANSFER,
                'affects_totals' => false,
                'exclusion_reason' => 'self_transfer_participant',
                'is_internal_transfer' => true,
                'paired_transaction_id' => null,
            ]);
            $matches++;
        }

        return $matches;
    }

    private function isStrongMatch(PluggyTransaction $debit, PluggyTransaction $credit): bool
    {
        if ($debit->pluggy_account_id === $credit->pluggy_account_id
            || (float) $debit->amount !== (float) $credit->amount
            || $debit->transaction_date->diffInDays($credit->transaction_date) > 2) {
            return false;
        }

        $debitPayment = $debit->payment_data ?? [];
        $creditPayment = $credit->payment_data ?? [];
        $debitReference = $debitPayment['referenceNumber'] ?? null;
        $creditReference = $creditPayment['referenceNumber'] ?? null;

        if ($this->isTransfer($debitPayment, $creditPayment) && $debitReference && $debitReference === $creditReference) {
            return true;
        }

        return $this->participantMatchesAccount($debitPayment['receiver'] ?? [], $credit->account)
            && $this->participantMatchesAccount($creditPayment['payer'] ?? [], $debit->account);
    }

    /** @param array<string, mixed> $payment */
    private function isTransfer(array $debitPayment, array $creditPayment): bool
    {
        $method = strtoupper((string) (($debitPayment['paymentMethod'] ?? '') ?: ($creditPayment['paymentMethod'] ?? '')));

        return in_array($method, ['PIX', 'TED', 'DOC', 'TRANSFER'], true);
    }

    /** @param array<string, mixed> $participant */
    private function participantMatchesAccount(array $participant, ?PluggyAccount $account): bool
    {
        if (! $account) {
            return false;
        }

        $document = data_get($participant, 'documentNumber.value');
        if ($document && $account->owner_document_hash === $this->fingerprint($document)) {
            return true;
        }

        $accountNumber = $participant['accountNumber'] ?? null;

        return $accountNumber && $account->account_number_hash === $this->fingerprint($accountNumber);
    }

    /** @param Collection<int, PluggyAccount> $accounts */
    private function participantMatchesAnyAccount(array $participant, Collection $accounts): bool
    {
        return $accounts->contains(fn (PluggyAccount $account) => $this->participantMatchesAccount($participant, $account));
    }

    private function fingerprint(string $value): string
    {
        return hash_hmac('sha256', preg_replace('/\D/', '', $value), (string) config('app.key'));
    }
}
