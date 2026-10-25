<?php $title = $customer->exists ? 'แก้ไขลูกค้า' : 'เพิ่มลูกค้า'; require __DIR__.'/../layouts/header.php'; ?>
<section class="card">
    <?php require __DIR__.'/../partials/messages.php'; ?>
    <form method="post" action="<?= e($customer->exists ? route('customers.update', $customer) : route('customers.store')) ?>">
        <?= csrf_field() ?><?= $customer->exists ? method_field('patch') : '' ?>
        <label for="name">ชื่อลูกค้า</label><input id="name" name="name" maxlength="255" value="<?= e(\App\Support\FormValue::old('name', $customer->name)) ?>">
        <label for="phone">เบอร์โทร *</label><input id="phone" name="phone" type="tel" maxlength="15" required value="<?= e(\App\Support\FormValue::old('phone', $customer->phone)) ?>">
        <div class="actions"><button>บันทึกลูกค้า</button><a href="<?= e(route('customers.index')) ?>">กลับรายการ</a></div>
    </form>
</section>
<?php require __DIR__.'/../layouts/footer.php'; ?>
