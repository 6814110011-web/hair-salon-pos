<section class="card">
    <h2>ปิดใช้งานบัญชี</h2>
    <p>เมื่อปิดใช้งาน คุณจะเข้าสู่ระบบไม่ได้ ประวัติการขายจะยังคงอยู่ กรุณากรอกรหัสผ่านเพื่อยืนยัน</p>
    <?php $errorBag = 'userDeletion'; $successStatus = ''; unset($successMessage); ?>
    <?php require __DIR__.'/../../partials/messages.php'; ?>
    <form method="post" action="<?= e(route('profile.destroy')) ?>" data-confirm="ยืนยันการปิดใช้งานบัญชีหรือไม่?">
        <?= csrf_field() ?>
        <?= method_field('delete') ?>
        <label for="delete_password">รหัสผ่าน</label>
        <input id="delete_password" name="password" type="password" required autocomplete="current-password">
        <button type="submit" class="button-danger">ปิดใช้งานบัญชี</button>
    </form>
</section>
