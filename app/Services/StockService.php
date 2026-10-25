<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockService
{
    public function receive(User $actor, Product $product, int $quantity, string $note): StockMovement
    {
        return $this->change($actor, $product, $quantity, $note, false);
    }

    public function adjust(User $actor, Product $product, int $targetQuantity, string $reason): StockMovement
    {
        return $this->change($actor, $product, $targetQuantity, $reason, true);
    }

    private function change(User $actor, Product $product, int $value, string $reason, bool $adjust): StockMovement
    {
        $operator = $actor->fresh();
        abort_unless($operator?->is_active && $operator->hasRole('admin'), 403);
        if (trim($reason) === '' || mb_strlen($reason) > 255 || $value < ($adjust ? 0 : 1) || $value > 2147483647) {
            throw ValidationException::withMessages(['quantity' => 'จำนวนหรือเหตุผลไม่ถูกต้อง']);
        }

        return DB::transaction(function () use ($actor, $product, $value, $reason, $adjust) {
            $locked = Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            $after = $adjust ? $value : $locked->stock_qty + $value;
            $delta = $after - $locked->stock_qty;
            if ($after > 2147483647 || $delta === 0) {
                throw ValidationException::withMessages(['quantity' => 'ยอดสต็อกเกินขอบเขตหรือไม่มีการเปลี่ยนแปลง']);
            }
            $locked->forceFill(['stock_qty' => $after])->save();

            return StockMovement::forceCreate(['product_id' => $locked->id, 'user_id' => $actor->id, 'type' => $adjust ? 'adjust' : 'in', 'qty' => $adjust ? $delta : $value, 'stock_after' => $after, 'note' => trim($reason)]);
        });
    }
}
