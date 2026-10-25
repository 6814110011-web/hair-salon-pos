<?php
$formErrors = $errors->getBag($errorBag ?? 'default');
?>
<?php if (session('success')): ?>
    <p class="notice success" role="status"><?= e(session('success')) ?></p>
<?php endif; ?>
<?php if ($formErrors->any()): ?>
    <div class="notice error" role="alert">
        <ul>
            <?php foreach ($formErrors->all() as $message): ?>
                <li><?= e($message) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<?php if (session('status') && (! isset($successStatus) || session('status') === $successStatus)): ?>
    <p class="notice success" role="status"><?= e($successMessage ?? __(session('status'))) ?></p>
<?php endif; ?>
