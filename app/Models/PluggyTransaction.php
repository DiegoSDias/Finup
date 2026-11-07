<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PluggyTransaction extends Model
{
    use HasFactory;

    public const CLASSIFICATION_INCOME = 'income';

    public const CLASSIFICATION_EXPENSE = 'expense';

    public const CLASSIFICATION_INTERNAL_TRANSFER = 'internal_transfer';

    public const CLASSIFICATION_INTERNAL_RESERVE_TRANSFER = 'internal_reserve_transfer';

    public const CLASSIFICATION_CREDIT_CARD_PAYMENT = 'credit_card_payment';

    public const CLASSIFICATION_REFUND = 'refund';

    public const CLASSIFICATION_CASHBACK = 'cashback';

    public const CLASSIFICATION_FEE = 'fee';

    public const CLASSIFICATION_INVESTMENT = 'investment_movement';

    public const CLASSIFICATION_IGNORED = 'ignored';

    public const CLASSIFICATION_UNKNOWN = 'unknown';

    protected $fillable = [
        'user_id', 'pluggy_connection_id', 'pluggy_account_id', 'pluggy_transaction_id',
        'provider_id', 'provider_code', 'description', 'description_raw', 'transaction_date',
        'transaction_at', 'amount', 'currency_code', 'direction', 'status', 'operation_type',
        'classification', 'affects_totals', 'exclusion_reason', 'payment_method', 'pluggy_category_id',
        'pluggy_category_name', 'category_id', 'category_source', 'is_installment',
        'current_installment', 'total_installments', 'purchase_date', 'payment_data',
        'credit_card_metadata', 'raw_payload', 'is_internal_transfer', 'paired_transaction_id',
        'removed_at', 'deleted_by_user_at', 'user_modified_description', 'user_modified_category',
        'user_modified_date', 'user_modified_payment_method', 'phrase_rules_restored',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date', 'transaction_at' => 'datetime', 'purchase_date' => 'date',
            'amount' => 'decimal:2', 'affects_totals' => 'boolean', 'is_installment' => 'boolean',
            'is_internal_transfer' => 'boolean', 'payment_data' => 'array',
            'credit_card_metadata' => 'array', 'raw_payload' => 'array', 'removed_at' => 'datetime',
            'deleted_by_user_at' => 'datetime', 'user_modified_description' => 'boolean',
            'user_modified_category' => 'boolean', 'user_modified_date' => 'boolean',
            'user_modified_payment_method' => 'boolean', 'phrase_rules_restored' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(PluggyItem::class, 'pluggy_connection_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(PluggyAccount::class, 'pluggy_account_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function pairedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'paired_transaction_id');
    }

    public function payableSettlement(): HasOne
    {
        return $this->hasOne(PayableSettlement::class);
    }

    public function scopeContributesToTotals(Builder $query): Builder
    {
        return $query->whereNull('removed_at')
            ->whereNull('deleted_by_user_at')
            ->where('affects_totals', true)
            ->where(function (Builder $query): void {
                $query->where('status', 'POSTED')
                    ->orWhere(function (Builder $query): void {
                        $query->whereIn('classification', [
                            self::CLASSIFICATION_REFUND,
                            self::CLASSIFICATION_CASHBACK,
                        ])->orWhere(function (Builder $query): void {
                            $query->where('classification', self::CLASSIFICATION_EXPENSE)
                                ->whereHas('account', fn (Builder $account) => $account->where('type', 'CREDIT'));
                        });
                    });
            });
    }

    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('transaction_date', $year)->whereMonth('transaction_date', $month);
    }

    public function scopeEconomicIncome(Builder $query): Builder
    {
        return $query->where('classification', self::CLASSIFICATION_INCOME);
    }

    public function scopeEconomicExpenses(Builder $query): Builder
    {
        return $query->whereIn('classification', [self::CLASSIFICATION_EXPENSE, self::CLASSIFICATION_FEE]);
    }

    public function scopeReversals(Builder $query): Builder
    {
        return $query->whereIn('classification', [self::CLASSIFICATION_REFUND, self::CLASSIFICATION_CASHBACK]);
    }

    public function getPaymentModeAttribute(): string
    {
        return match ($this->payment_method) {
            'pix' => 'pix', 'card' => 'cartao',
            'debito', 'credito', 'dinheiro' => $this->payment_method,
            default => 'other',
        };
    }

    public function getPaymentLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'pix' => 'Pix', 'card' => 'Cartão — modalidade não informada',
            'debito' => 'Cartão de débito', 'credito' => 'Cartão de crédito',
            'dinheiro' => 'Dinheiro', default => 'Outro ou não identificado',
        };
    }

    public function getDateAttribute()
    {
        return $this->transaction_date;
    }

    public function getDisplayAmountAttribute(): float
    {
        return in_array($this->classification, [self::CLASSIFICATION_REFUND, self::CLASSIFICATION_CASHBACK], true)
            ? -1 * (float) $this->amount
            : (float) $this->amount;
    }
}
