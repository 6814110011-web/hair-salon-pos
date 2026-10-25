<?php $title = 'ลูกค้า'; require __DIR__.'/../layouts/header.php'; ?>
<div class="page-actions"><p>ข้อมูลสมาชิกและประวัติใช้บริการ</p><a class="button" href="<?= e(route('customers.create')) ?>">+ เพิ่มลูกค้า</a></div>
<?php require __DIR__.'/../partials/messages.php'; ?>
<section class="card">
    <form class="filter-form" method="get"><label for="q">ค้นหาชื่อหรือเบอร์โทร</label><div class="filter-controls"><input id="q" name="q" value="<?= e($q) ?>"><button>ค้นหา</button></div></form>
    <div class="table-wrap"><table><thead><tr><th>ชื่อ</th><th>เบอร์โทร</th><th>จำนวนครั้ง</th><th>จัดการ</th></tr></thead><tbody>
    <?php foreach ($customers as $customer): ?>
        <tr><td><?= e($customer->name ?: 'ไม่ระบุชื่อ') ?></td><td><?= e($customer->phone) ?></td><td><?= e($customer->total_visits) ?></td><td><a href="<?= e(route('customers.show', $customer)) ?>">ดูประวัติ</a> · <a href="<?= e(route('customers.edit', $customer)) ?>">แก้ไข</a></td></tr>
    <?php endforeach; ?>
    <?php if ($customers->isEmpty()): ?><tr><td colspan="4" class="empty-state">ยังไม่มีลูกค้าตามเงื่อนไข เพิ่มลูกค้าใหม่เพื่อเริ่มใช้งาน</td></tr><?php endif; ?>
    </tbody></table></div>
    <?php $paginator = $customers; require __DIR__.'/../partials/pagination.php'; ?>
</section>
<?php require __DIR__.'/../layouts/footer.php'; ?>
