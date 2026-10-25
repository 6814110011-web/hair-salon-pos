<?php $title = 'ตั้งรหัสผ่านใหม่'; $guest = true; ?>
<?php require __DIR__.'/../layouts/header.php'; ?>
<section class="card">
    <?php require __DIR__.'/../partials/messages.php'; ?>
    <form method="post" action="<?= e(route('password.store')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($request->route('token')) ?>">
        <label for="email">อีเมล</label>
        <input id="email" name="email" type="email" value="<?= e(\App\Support\FormValue::old('email', $request->email)) ?>" required autofocus autocomplete="username">
        <label for="password">รหัสผ่านใหม่</label>
        <input id="password" name="password" type="password" required autocomplete="new-password">
        <label for="password_confirmation">ยืนยันรหัสผ่านใหม่</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        <button type="submit">บันทึกรหัสผ่านใหม่</button>
    </form>
</section>
<?php require __DIR__.'/../layouts/footer.php'; ?>
