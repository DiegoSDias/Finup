<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayableSettlement extends Model
{
    protected $fillable = ['account_payable_id', 'user_id', 'manual_expense_id', 'pluggy_transaction_id', 'paid_on'];

    protected function casts(): array
    {
        return ['paid_on' => 'date'];
    }

    public function payable(): BelongsTo
    {
        return $this->belongsTo(AccountPayable::class, 'account_payable_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function manualExpense(): BelongsTo
    {
        return $this->belongsTo(Expense::class, 'manual_expense_id');
    }

    public function pluggyTransaction(): BelongsTo
    {
        return $this->belongsTo(PluggyTransaction::class);
    }
}
