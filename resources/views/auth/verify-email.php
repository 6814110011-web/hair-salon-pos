<?php $title = 'ยืนยันอีเมล'; $guest = true; ?>
<?php require __DIR__.'/../layouts/header.php'; ?>
<section class="card">
    <p>กรุณากดลิงก์ในอีเมลที่ส่งให้เพื่อยืนยันอีเมลของคุณ หากยังไม่ได้รับ สามารถส่งใหม่ได้</p>
    <?php $successStatus = 'verification-link-sent'; $successMessage = 'ส่งลิงก์ยืนยันอีเมลใหม่แล้ว'; ?>
    <?php require __DIR__.'/../partials/messages.php'; ?>
    <div class="actions">
        <form method="post" action="<?= e(route('verification.send')) ?>">
            <?= csrf_field() ?>
            <button type="submit">ส่งอีเมลยืนยันอีกครั้ง</button>
        </form>
        <form method="post" action="<?= e(route('logout')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="button-secondary">ออกจากระบบ</button>
        </form>
    </div>
</section>
<?php require __DIR__.'/../layouts/footer.php'; ?>
