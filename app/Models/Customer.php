<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'phone'];

    protected function casts(): array
    {
        return ['total_visits' => 'integer', 'last_visit_at' => 'datetime'];
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}
