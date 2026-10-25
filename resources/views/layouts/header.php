<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($title ?? 'Hair Salon POS') ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css').'?v='.filemtime(public_path('css/app.css'))) ?>">
    <script src="<?= e(asset('js/app.js').'?v='.filemtime(public_path('js/app.js'))) ?>" defer></script>
</head>
<?php $hasSidebar = auth()->check() && empty($guest); ?>
<body class="<?= $hasSidebar ? 'app-shell' : 'guest-shell' ?>">
<header class="site-header">
    <div class="header-brand"><?php if ($hasSidebar): ?><button type="button" id="menu-toggle" class="button-secondary menu-toggle" aria-label="เปิดปิดเมนู" aria-expanded="false" aria-controls="sidebar">☰</button><?php endif; ?><a class="brand" href="<?= e(url('/')) ?>"><span class="brand-mark">HS</span><span>Hair Salon <strong>POS</strong></span></a></div>
    <nav class="top-actions" aria-label="บัญชีผู้ใช้">
        <?php if (auth()->check()): ?>
            <a href="<?= e(route('profile.edit')) ?>" <?= request()->routeIs('profile.*') ? 'aria-current="page"' : '' ?>>โปรไฟล์</a>
            <span class="account-name"><?= e(auth()->user()->name) ?><small><?= e(['admin' => 'ผู้ดูแลระบบ', 'cashier' => 'แคชเชียร์', 'stylist' => 'ช่างทำผม'][auth()->user()->role]) ?></small></span>
            <form method="post" action="<?= e(route('logout')) ?>">
                <?= csrf_field() ?>
                <button type="submit" class="button-secondary">ออกจากระบบ</button>
            </form>
        <?php else: ?>
            <a href="<?= e(route('login')) ?>">เข้าสู่ระบบ</a>
        <?php endif; ?>
    </nav>
</header>
<?php if ($hasSidebar): ?>
<aside id="sidebar" class="sidebar"><p class="sidebar-caption">จัดการร้านทำผม</p><nav aria-label="เมนูหลัก">
<?php $navigationItems = [
    ['dashboard', 'dashboard', 'ภาพรวม', '01', ['admin', 'cashier', 'stylist']],
    ['customers.index', 'customers.*', 'ลูกค้า', '02', ['admin', 'cashier']],
    ['employees.index', 'employees.*', 'พนักงาน', '03', ['admin']],
    ['products.index', 'products.*', 'สินค้า / สต็อก', '04', ['admin', 'cashier']],
    ['services.index', 'services.*', 'บริการ', '05', ['admin', 'cashier', 'stylist']],
    ['sales.index', 'sales.*', 'ขาย / ใบเสร็จ', '06', ['admin', 'cashier']],
    ['reports.index', 'reports.*', 'รายงาน', '07', ['admin']],
]; ?>
<?php foreach ($navigationItems as [$navigationRoute, $navigationPattern, $navigationLabel, $navigationNumber, $navigationRoles]): ?>
<?php if (auth()->user()->hasRole(...$navigationRoles)): ?><a href="<?= e(route($navigationRoute)) ?>" <?= request()->routeIs($navigationPattern) ? 'aria-current="page"' : '' ?>><span class="menu-number"><?= e($navigationNumber) ?></span><?= e($navigationLabel) ?></a><?php endif; ?>
<?php endforeach; ?>
</nav><div class="sidebar-note"><span class="status-dot"></span> ระบบจัดการหน้าร้าน<small>บริการ · สินค้า · การขาย</small><p><a href="<?= e(route('profile.edit')) ?>">จัดการโปรไฟล์ของฉัน</a></p></div></aside>
<?php endif; ?>
<main class="<?= $hasSidebar ? 'app-content' : (! empty($guest) ? 'container auth-container' : 'container') ?>">
    <h1><?= e($title ?? 'Hair Salon POS') ?></h1>
