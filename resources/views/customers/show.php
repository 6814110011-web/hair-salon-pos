<?php $title = $customer->name ?: 'ข้อมูลลูกค้า'; require __DIR__.'/../layouts/header.php'; ?>
<?php require __DIR__.'/../partials/messages.php'; ?>
<section class="card"><div class="page-actions"><div><h2><?= e($customer->phone) ?></h2><p>ใช้บริการ <?= e($customer->total_visits) ?> ครั้ง · ล่าสุด <?= e($customer->last_visit_at?->format('d/m/Y H:i') ?? 'ยังไม่มีประวัติ') ?></p></div><a class="button button-secondary" href="<?= e(route('customers.edit', $customer)) ?>">แก้ไขข้อมูล</a></div></section>
<section class="card"><h2>ประวัติใช้บริการ</h2><div class="table-wrap"><table><thead><tr><th>บิล / วันที่</th><th>รายการ</th><th>ยอดสุทธิ</th><th>สถานะ</th></tr></thead><tbody>
<?php foreach ($sales as $sale): ?>
<tr><td><?php if (\Illuminate\Support\Facades\Route::has('sales.show') && (auth()->user()->hasRole('admin') || $sale->user_id === auth()->id())): ?><a href="<?= e(route('sales.show', $sale)) ?>"><?= e($sale->bill_no) ?></a><?php else: ?><?= e($sale->bill_no) ?><?php endif; ?><small><?= e($sale->created_at?->format('d/m/Y H:i')) ?></small></td><td><?php foreach ($sale->items as $item): ?><div><?= e($item->item_name) ?> × <?= e($item->qty) ?></div><?php endforeach; ?></td><td><?= e($sale->total) ?> บาท</td><td><?= e(['paid' => 'ชำระแล้ว', 'pending' => 'รอชำระ', 'cancelled' => 'ยกเลิก'][$sale->status]) ?></td></tr>
<?php endforeach; ?>
<?php if ($sales->isEmpty()): ?><tr><td colspan="4" class="empty-state">ยังไม่มีประวัติใช้บริการ</td></tr><?php endif; ?>
</tbody></table></div><?php $paginator = $sales; require __DIR__.'/../partials/pagination.php'; ?></section>
<a href="<?= e(route('customers.index')) ?>">← กลับรายการลูกค้า</a>
<?php require __DIR__.'/../layouts/footer.php'; ?>
