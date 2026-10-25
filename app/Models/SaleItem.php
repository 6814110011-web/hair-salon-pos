<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SaleItem extends Model
{
    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'subtotal' => 'decimal:2', 'qty' => 'integer'];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function stylist(): BelongsTo
    {
        return $this->belongsTo(User::class, 'stylist_id');
    }

    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }
}
