<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PluggyCategoryRule extends Model
{
    protected $fillable = ['user_id', 'category_id', 'phrase', 'starts_on', 'ends_before'];

    protected function casts(): array { return ['starts_on' => 'date', 'ends_before' => 'date']; }

    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
}
