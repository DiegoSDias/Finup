<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Expense extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'date',
        'month',
        'year',
        'payment_mode',
        'manual_bank_id',
        'bank_status',
        'type',
        'description',
        'amount',
        'is_installment',
        'current_installment',
        'total_installments',
        'installments_group',
        'recurring_expense_id',
        'recurrence_occurrence_date',
        'recurrence_is_override',
        'recurrence_status',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
        'recurrence_occurrence_date' => 'date', 'recurrence_is_override' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function manualBank(): BelongsTo
    {
        return $this->belongsTo(ManualBank::class);
    }

    public function recurringExpense(): BelongsTo
    {
        return $this->belongsTo(RecurringExpense::class);
    }

    public function payableSettlement(): HasOne
    {
        return $this->hasOne(PayableSettlement::class, 'manual_expense_id');
    }

    public function getBankLabelAttribute(): string
    {
        return match ($this->bank_status) {
            'bank' => $this->manualBank?->name ?? 'Banco indisponível',
            'none' => 'Não realizado por meio de um banco',
            default => 'Banco não informado',
        };
    }

    public function getPaymentLabelAttribute(): string
    {
        return match ($this->payment_mode) {
            'pix' => 'Pix', 'dinheiro' => 'Dinheiro', 'debito' => 'Cartão de débito',
            'credito' => 'Cartão de crédito', 'cartao' => 'Cartão — modalidade não informada',
            default => 'Não informado',
        };
    }

    public function scopeFromUser(Builder $query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeIncome(Builder $query)
    {
        return $query->where('type', 'income');
    }

    public function scopeExpense(Builder $query)
    {
        return $query->where('type', 'expense');
    }

    public function scopeIncludedInTotals(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->whereNull('recurrence_status')
            ->orWhere('recurrence_status', 'active'));
    }

    public function scopeYear(Builder $query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeGroupedByMonth(Builder $query): Builder
    {
        return $query
            ->selectRaw('month, SUM(amount) as total')
            ->groupBy('month');
    }
}
