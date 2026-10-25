<?php $title = 'ลืมรหัสผ่าน'; $guest = true; ?>
<?php require __DIR__.'/../layouts/header.php'; ?>
<section class="card">
    <p>กรอกอีเมลเพื่อรับลิงก์ตั้งรหัสผ่านใหม่</p>
    <?php require __DIR__.'/../partials/messages.php'; ?>
    <form method="post" action="<?= e(route('password.email')) ?>">
        <?= csrf_field() ?>
        <label for="email">อีเมล</label>
        <input id="email" name="email" type="email" value="<?= e(\App\Support\FormValue::old('email')) ?>" required autofocus autocomplete="email">
        <button type="submit">ส่งลิงก์ตั้งรหัสผ่านใหม่</button>
    </form>
    <p><a href="<?= e(route('login')) ?>">กลับไปเข้าสู่ระบบ</a></p>
</section>
<?php require __DIR__.'/../layouts/footer.php'; ?>
