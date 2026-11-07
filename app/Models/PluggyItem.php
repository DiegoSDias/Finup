<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PluggyItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'pluggy_item_id',
        'last_event',
        'last_error',
        'deleted_at',
        'connector_id',
        'connector_name',
        'display_name',
        'connector_image_url',
        'status',
        'execution_status',
        'last_sync_started_at',
        'last_synced_at',
        'last_sync_status',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_sync_started_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDisplayLabelAttribute(): string
    {
        return $this->display_name ?: ($this->connector_name ?: 'Instituição não informada');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(PluggyAccount::class, 'pluggy_connection_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PluggyTransaction::class, 'pluggy_connection_id');
    }
}
