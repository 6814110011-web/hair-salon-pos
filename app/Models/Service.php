<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    public const CATEGORIES = ['cut' => 'ตัดผม', 'wash' => 'สระผม', 'color' => 'ทำสี', 'perm' => 'ดัดผม', 'straighten' => 'ยืดผม', 'treatment' => 'ทรีตเมนต์', 'other' => 'อื่น ๆ'];

    protected $fillable = ['name', 'category', 'price', 'duration_minutes', 'is_active'];

    protected function casts(): array
    {
        return ['price' => 'decimal:2', 'duration_minutes' => 'integer', 'is_active' => 'boolean'];
    }
}
