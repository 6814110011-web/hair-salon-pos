<?php $title = $service->exists ? 'แก้ไขบริการ' : 'เพิ่มบริการ'; require __DIR__.'/../layouts/header.php'; ?>
<section class="card"><?php require __DIR__.'/../partials/messages.php'; ?><form method="post" action="<?= e($service->exists ? route('services.update', $service) : route('services.store')) ?>"><?= csrf_field() ?><?= $service->exists ? method_field('patch') : '' ?>
<label for="name">ชื่อบริการ *</label><input id="name" name="name" required maxlength="255" value="<?= e(\App\Support\FormValue::old('name', $service->name)) ?>">
<label for="category">ประเภท *</label><select id="category" name="category"><?php foreach (\App\Models\Service::CATEGORIES as $key => $label): ?><option value="<?= e($key) ?>" <?= \App\Support\FormValue::old('category', $service->category) === $key ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select>
<label for="price">ราคา (บาท) *</label><input id="price" name="price" type="number" min="0" max="99999999.99" step="0.01" required value="<?= e(\App\Support\FormValue::old('price', $service->price)) ?>">
<label for="duration_minutes">ระยะเวลา (นาที) *</label><input id="duration_minutes" name="duration_minutes" type="number" min="1" max="2147483647" required value="<?= e(\App\Support\FormValue::old('duration_minutes', $service->duration_minutes)) ?>">
<input name="is_active" type="hidden" value="0"><label class="checkbox"><input name="is_active" type="checkbox" value="1" <?= \App\Support\FormValue::old('is_active', $service->is_active) ? 'checked' : '' ?>> เปิดให้บริการ</label>
<div class="actions"><button>บันทึกบริการ</button><a href="<?= e(route('services.index')) ?>">กลับรายการ</a></div></form></section>
<?php require __DIR__.'/../layouts/footer.php'; ?>
