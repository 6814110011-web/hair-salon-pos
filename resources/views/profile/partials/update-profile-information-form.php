<section class="card">
    <h2>ข้อมูลโปรไฟล์</h2>
    <?php $errorBag = 'default'; $successStatus = 'profile-updated'; $successMessage = 'บันทึกโปรไฟล์แล้ว'; ?>
    <?php require __DIR__.'/../../partials/messages.php'; ?>
    <form method="post" action="<?= e(route('profile.update')) ?>">
        <?= csrf_field() ?>
        <?= method_field('patch') ?>
        <label for="name">ชื่อ</label>
        <input id="name" name="name" value="<?= e(\App\Support\FormValue::old('name', $user->name)) ?>" required autocomplete="name">
        <label for="email">อีเมล</label>
        <input id="email" name="email" type="email" value="<?= e(\App\Support\FormValue::old('email', $user->email)) ?>" required autocomplete="username">
        <button type="submit">บันทึกโปรไฟล์</button>
    </form>
    <?php if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail()): ?>
        <p>อีเมลของคุณยังไม่ได้รับการยืนยัน</p>
        <form method="post" action="<?= e(route('verification.send')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="button-secondary">ส่งอีเมลยืนยันอีกครั้ง</button>
        </form>
        <?php if (session('status') === 'verification-link-sent'): ?>
            <p class="notice success" role="status">ส่งลิงก์ยืนยันอีเมลใหม่แล้ว</p>
        <?php endif; ?>
    <?php endif; ?>
</section>
