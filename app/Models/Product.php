<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = ['sku', 'name', 'cost_price', 'sell_price', 'min_stock', 'unit', 'is_active'];

    protected function casts(): array
    {
        return ['cost_price' => 'decimal:2', 'sell_price' => 'decimal:2', 'stock_qty' => 'integer', 'min_stock' => 'integer', 'is_active' => 'boolean'];
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
