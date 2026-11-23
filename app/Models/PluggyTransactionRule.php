<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluggyTransactionRule extends Model
{
    use HasFactory;

    public const ACTION_EXCLUDE = 'exclude_from_totals';
    public const ACTION_INTERNAL_TRANSFER = 'internal_transfer';

    protected $fillable = ['user_id', 'phrase', 'action', 'is_active', 'pluggy_connection_id', 'ends_before'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'ends_before' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(PluggyItem::class, 'pluggy_connection_id');
    }
}
