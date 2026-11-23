<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringExpense extends Model
{
    public const FREQUENCIES = ['monthly' => 1, 'quarterly' => 3, 'semiannual' => 6, 'annual' => 12];

    protected $fillable = [
        'user_id', 'category_id', 'manual_bank_id', 'bank_status', 'payment_mode', 'description', 'amount',
        'frequency', 'start_date', 'end_date', 'is_active', 'ended_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'start_date' => 'date', 'end_date' => 'date', 'is_active' => 'boolean', 'ended_at' => 'datetime'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function manualBank(): BelongsTo { return $this->belongsTo(ManualBank::class); }
    public function occurrences(): HasMany { return $this->hasMany(Expense::class, 'recurring_expense_id'); }

    public function canBeDeleted(): bool
    {
        $hasFutureOccurrence = $this->occurrences()
            ->where('recurrence_status', 'active')
            ->whereDate('recurrence_occurrence_date', '>=', now()->toDateString())
            ->exists();

        return ! $hasFutureOccurrence && (! $this->is_active || ($this->end_date && $this->end_date->isBefore(today())));
    }
}
