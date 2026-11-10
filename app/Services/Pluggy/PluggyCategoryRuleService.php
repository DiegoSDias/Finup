<?php

namespace App\Services\Pluggy;

use App\Models\PluggyCategoryRule;
use App\Models\PluggyTransaction;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class PluggyCategoryRuleService
{
    public function __construct(private readonly PluggyTransactionClassifier $classifier) {}

    /** @var array<int, Collection> */
    private array $rules = [];

    public function resolve(int $userId, string $description, ?string $raw, string $date, string $type): ?int
    {
        $text = $this->normalize($description.' '.($raw ?? ''));
        $rules = $this->rules[$userId] ??= PluggyCategoryRule::query()->where('user_id', $userId)
            ->with(['category' => fn ($q) => $q->where('user_id', $userId)])
            ->orderBy('id')->get()->sortByDesc(fn ($rule) => mb_strlen($this->normalize($rule->phrase)));
        return $rules->first(fn ($rule) => $rule->category?->type === $type
            && ($rule->starts_on === null || $rule->starts_on->toDateString() <= $date)
            && ($rule->ends_before === null || $rule->ends_before->toDateString() > $date)
            && str_contains($text, $this->normalize($rule->phrase)))?->category_id;
    }

    public function reclassify(int $userId): void
    {
        unset($this->rules[$userId]);
        PluggyTransaction::query()->where('user_id', $userId)->whereNull('removed_at')->whereNull('deleted_by_user_at')
            ->where('user_modified_category', false)->where('category_source', '!=', 'user')->orderBy('id')->chunkById(250, function ($transactions) use ($userId): void {
                foreach ($transactions as $transaction) {
                    $payload = $transaction->raw_payload ?? [];
                    $base = $payload ? $this->classifier->classify($payload, 'BANK')['classification'] : $transaction->classification;
                    $type = $base === PluggyTransaction::CLASSIFICATION_INCOME ? 'income' : 'expense';
                    $categoryId = $this->resolve($userId, $payload['description'] ?? $transaction->description, $transaction->description_raw, $transaction->transaction_date->toDateString(), $type);
                    $transaction->update(['category_id' => $categoryId, 'category_source' => $categoryId ? 'rule' : 'pluggy']);
                }
            });
    }

    private function normalize(string $value): string { return trim(Str::lower(Str::ascii($value))); }
}
