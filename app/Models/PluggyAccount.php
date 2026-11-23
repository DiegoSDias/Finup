<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PluggyAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'pluggy_connection_id', 'pluggy_account_id', 'name', 'type', 'subtype',
        'number_last_four', 'currency_code', 'balance', 'owner_document_hash',
        'account_number_hash', 'routing_number_hash', 'is_active', 'last_synced_at',
    ];

    protected function casts(): array
    {
        return ['balance' => 'decimal:2', 'is_active' => 'boolean', 'last_synced_at' => 'datetime'];
    }

    public function connection(): BelongsTo
    {
        return $this->belongsTo(PluggyItem::class, 'pluggy_connection_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PluggyTransaction::class);
    }
}
