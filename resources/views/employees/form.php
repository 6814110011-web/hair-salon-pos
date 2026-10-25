<?php $title = $employee->exists ? 'แก้ไขพนักงาน' : 'เพิ่มพนักงาน'; require __DIR__.'/../layouts/header.php'; ?>
<section class="card"><?php require __DIR__.'/../partials/messages.php'; ?>
<form method="post" action="<?= e($employee->exists ? route('employees.update', $employee) : route('employees.store')) ?>">
<?= csrf_field() ?><?= $employee->exists ? method_field('patch') : '' ?>
<label for="name">ชื่อ *</label><input id="name" name="name" required maxlength="255" value="<?= e(\App\Support\FormValue::old('name', $employee->name)) ?>">
<label for="email">อีเมล *</label><input id="email" type="email" name="email" required maxlength="255" value="<?= e(\App\Support\FormValue::old('email', $employee->email)) ?>">
<label for="phone">เบอร์โทร</label><input id="phone" type="tel" name="phone" maxlength="15" value="<?= e(\App\Support\FormValue::old('phone', $employee->phone)) ?>">
<label for="role">บทบาท *</label><select id="role" name="role"><?php foreach (['admin' => 'ผู้ดูแลระบบ', 'cashier' => 'แคชเชียร์', 'stylist' => 'ช่างทำผม'] as $key => $label): ?><option value="<?= e($key) ?>" <?= \App\Support\FormValue::old('role', $employee->role) === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
<label for="commission_rate">คอมมิชชัน (%)</label><input id="commission_rate" type="number" name="commission_rate" min="0" max="100" step="0.01" required value="<?= e(\App\Support\FormValue::old('commission_rate', $employee->commission_rate ?? '0')) ?>">
<input type="hidden" name="is_active" value="0"><label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= \App\Support\FormValue::old('is_active', $employee->is_active) ? 'checked' : '' ?>> เปิดใช้งานบัญชี</label>
<label for="password">รหัสผ่าน <?= $employee->exists ? '(เว้นว่างเพื่อใช้รหัสเดิม)' : '*' ?></label><input id="password" name="password" type="password" minlength="8" autocomplete="new-password" <?= $employee->exists ? '' : 'required' ?>>
<label for="password_confirmation">ยืนยันรหัสผ่าน</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
<div class="actions"><button>บันทึกพนักงาน</button><a href="<?= e(route('employees.index')) ?>">กลับรายการ</a></div></form></section>
<?php require __DIR__.'/../layouts/footer.php'; ?>
