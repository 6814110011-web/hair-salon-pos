<?php $title = 'ยืนยันรหัสผ่าน'; $guest = true; ?>
<?php require __DIR__.'/../layouts/header.php'; ?>
<section class="card">
    <p>กรอกรหัสผ่านเพื่อยืนยันตัวตนก่อนดำเนินการต่อ</p>
    <?php require __DIR__.'/../partials/messages.php'; ?>
    <form method="post" action="<?= e(route('password.confirm')) ?>">
        <?= csrf_field() ?>
        <label for="password">รหัสผ่าน</label>
        <input id="password" name="password" type="password" required autofocus autocomplete="current-password">
        <button type="submit">ยืนยัน</button>
    </form>
</section>
<?php require __DIR__.'/../layouts/footer.php'; ?>
