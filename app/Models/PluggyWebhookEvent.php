<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PluggyWebhookEvent extends Model
{
    protected $fillable = [
        'event_id', 'event', 'pluggy_item_id', 'payload', 'received_at', 'processed_at', 'processing_error',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array', 'received_at' => 'datetime', 'processed_at' => 'datetime'];
    }
}
