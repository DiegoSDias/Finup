<?php

namespace App\Services\Pluggy;

use App\Models\PluggyTransaction;
use App\Models\PluggyTransactionRule;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Services\MonthlyBalanceService;

class PluggyTransactionRuleService
{
    /** @var array<int, Collection<int, PluggyTransactionRule>> */
    private array $rules = [];

    public function __construct(
        private readonly PluggyTransactionClassifier $classifier,
        private readonly InternalTransferMatcher $transferMatcher,
    ) {
    }

    /**
     * @param array{classification: string, affects_totals: bool, payment_method: string} $classification
     * @return array{classification: string, affects_totals: bool, payment_method: string, exclusion_reason: ?string, is_internal_transfer: bool}
     */
    public function apply(int $userId, string $description, ?string $descriptionRaw, array $classification, ?int $connectionId = null, ?string $date = null, bool $restored = false): array
    {
        $rule = $restored ? null : $this->matchingRule($userId, $description, $descriptionRaw, $connectionId, $date);

        if (! $rule) {
            return [
                ...$classification,
                'exclusion_reason' => $classification['affects_totals'] ? null : $classification['classification'],
                'is_internal_transfer' => false,
            ];
        }

        $isTransfer = $rule->action === PluggyTransactionRule::ACTION_INTERNAL_TRANSFER;

        return [
            ...$classification,
            'classification' => $isTransfer
                ? PluggyTransaction::CLASSIFICATION_INTERNAL_TRANSFER
                : PluggyTransaction::CLASSIFICATION_IGNORED,
            'affects_totals' => false,
            'exclusion_reason' => $isTransfer ? 'user_transfer_phrase' : 'user_exclusion_phrase',
            'is_internal_transfer' => $isTransfer,
        ];
    }

    public function reclassifyForUser(int $userId): void
    {
        PluggyTransaction::query()
            ->with('account')
            ->where('user_id', $userId)
            ->whereNull('removed_at')
            ->whereNull('deleted_by_user_at')
            ->orderBy('id')
            ->chunkById(250, function (Collection $transactions) use ($userId): void {
                foreach ($transactions as $transaction) {
                    $payload = $transaction->raw_payload ?? [];
                    $base = $this->classifier->classify($payload, $transaction->account?->type ?? 'BANK');
                    $resolved = $this->apply(
                        $userId,
                        $payload['description'] ?? $transaction->description,
                        $transaction->description_raw,
                        $base,
                        $transaction->pluggy_connection_id,
                        $transaction->transaction_date->toDateString(),
                        $transaction->phrase_rules_restored,
                    );

                    $status = strtoupper((string) $transaction->status);
                    $affectsTotals = $this->shouldAffectTotals(
                        $resolved,
                        $status,
                        $transaction->account?->type ?? 'BANK',
                    );

                    $transaction->update([
                        'classification' => $resolved['classification'],
                        'affects_totals' => $affectsTotals,
                        'exclusion_reason' => $affectsTotals ? null : (in_array($resolved['exclusion_reason'], ['user_exclusion_phrase', 'user_transfer_phrase'], true) ? $resolved['exclusion_reason'] : ($status === 'POSTED' ? $resolved['exclusion_reason'] : 'pending')),
                        'is_internal_transfer' => $resolved['is_internal_transfer'],
                        'paired_transaction_id' => null,
                    ]);
                    PluggyTransaction::query()->whereKey($transaction->id)
                        ->where('user_modified_payment_method', false)
                        ->update(['payment_method' => $resolved['payment_method']]);
                }
            });

        $this->transferMatcher->matchForUser($userId);
        app(MonthlyBalanceService::class)->recalculateClosedFrom($userId, 0, 1);
    }

    private function matchingRule(int $userId, string $description, ?string $descriptionRaw, ?int $connectionId, ?string $date): ?PluggyTransactionRule
    {
        $haystack = $this->normalize($description . ' ' . ($descriptionRaw ?? ''));

        return $this->rulesForUser($userId)
            ->sortByDesc(fn (PluggyTransactionRule $rule) => mb_strlen($rule->phrase))
            ->first(fn (PluggyTransactionRule $rule) => ($rule->pluggy_connection_id === null || $rule->pluggy_connection_id === $connectionId)
                && ($rule->ends_before === null || ($date !== null && $date < $rule->ends_before->toDateString()))
                && str_contains($haystack, $this->normalize($rule->phrase)));
    }

    /** @return Collection<int, PluggyTransactionRule> */
    private function rulesForUser(int $userId): Collection
    {
        return $this->rules[$userId] ??= PluggyTransactionRule::query()
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->get();
    }

    private function normalize(string $value): string
    {
        return Str::lower(Str::ascii(trim($value)));
    }

    /** @param array{classification: string, affects_totals: bool} $classification */
    private function shouldAffectTotals(array $classification, string $status, string $accountType): bool
    {
        if (! $classification['affects_totals'] || $status === 'POSTED') {
            return $classification['affects_totals'];
        }

        return in_array($classification['classification'], [
            PluggyTransaction::CLASSIFICATION_REFUND,
            PluggyTransaction::CLASSIFICATION_CASHBACK,
        ], true) || ($accountType === 'CREDIT'
            && $classification['classification'] === PluggyTransaction::CLASSIFICATION_EXPENSE);
    }
}
