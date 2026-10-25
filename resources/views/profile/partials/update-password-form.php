<section class="card">
    <h2>เปลี่ยนรหัสผ่าน</h2>
    <?php $errorBag = 'updatePassword'; $successStatus = 'password-updated'; $successMessage = 'บันทึกรหัสผ่านแล้ว'; ?>
    <?php require __DIR__.'/../../partials/messages.php'; ?>
    <form method="post" action="<?= e(route('password.update')) ?>">
        <?= csrf_field() ?>
        <?= method_field('put') ?>
        <label for="current_password">รหัสผ่านปัจจุบัน</label>
        <input id="current_password" name="current_password" type="password" required autocomplete="current-password">
        <label for="new_password">รหัสผ่านใหม่</label>
        <input id="new_password" name="password" type="password" required autocomplete="new-password">
        <label for="password_confirmation">ยืนยันรหัสผ่านใหม่</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
        <button type="submit">บันทึกรหัสผ่าน</button>
    </form>
</section>
