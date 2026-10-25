<?php $title = 'ระบบจัดการร้านทำผม'; ?>
<?php require __DIR__.'/layouts/header.php'; ?>
<section class="card">
    <h2>ยินดีต้อนรับสู่ Hair Salon POS</h2>
    <p>เข้าสู่ระบบเพื่อเริ่มใช้งาน</p>
    <?php if (auth()->check()): ?>
        <a class="button" href="<?= e(route('dashboard')) ?>">ไปที่ Dashboard</a>
    <?php else: ?>
        <div class="actions">
            <a class="button" href="<?= e(route('login')) ?>">เข้าสู่ระบบ</a>
        </div>
    <?php endif; ?>
</section>
<?php require __DIR__.'/layouts/footer.php'; ?>
