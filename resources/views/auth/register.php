<?php $title = 'สมัครสมาชิก'; $guest = true; ?>
<?php require __DIR__.'/../layouts/header.php'; ?>
<section class="card">
    <?php require __DIR__.'/../partials/messages.php'; ?>
    <form method="post" action="<?= e(route('register')) ?>">
        <?= csrf_field() ?>
        <label for="name">ชื่อ</label>
        <input id="name" name="name" value="<?= e(\App\Support\FormValue::old('name')) ?>" required autofocus autocomplete="name">
        <label for="email">อีเมล</label>
        <input id="email" name="email" type="email" value="<?= e(\App\Support\FormValue::old('email')) ?>" required autocomplete="username">
        <label for="password">รหัสผ่าน</label>
        <input id="password" name="password" type="password" required autocomplete="new-password">
        <label for="password_confirmation">ยืนยันรหัสผ่าน</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        <button type="submit">สมัครสมาชิก</button>
    </form>
    <p><a href="<?= e(route('login')) ?>">มีบัญชีแล้ว? เข้าสู่ระบบ</a></p>
</section>
<?php require __DIR__.'/../layouts/footer.php'; ?>
