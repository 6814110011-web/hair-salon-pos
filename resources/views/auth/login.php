<?php $title = 'เข้าสู่ระบบ'; $guest = true; ?>
<?php require __DIR__.'/../layouts/header.php'; ?>
<section class="card">
    <?php require __DIR__.'/../partials/messages.php'; ?>
    <form method="post" action="<?= e(route('login')) ?>">
        <?= csrf_field() ?>
        <label for="email">อีเมล</label>
        <input id="email" name="email" type="email" value="<?= e(\App\Support\FormValue::old('email')) ?>" required autofocus autocomplete="username">
        <label for="password">รหัสผ่าน</label>
        <input id="password" name="password" type="password" required autocomplete="current-password">
        <label class="checkbox" for="remember">
            <input id="remember" name="remember" type="checkbox" value="1" <?= \App\Support\FormValue::old('remember') ? 'checked' : '' ?>> จดจำการเข้าสู่ระบบ
        </label>
        <button type="submit">เข้าสู่ระบบ</button>
    </form>
    <p><a href="<?= e(route('password.request')) ?>">ลืมรหัสผ่าน?</a></p>
</section>
<?php require __DIR__.'/../layouts/footer.php'; ?>
