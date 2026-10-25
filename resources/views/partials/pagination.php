<?php if ($paginator->hasPages()): ?>
<nav class="pagination" aria-label="แบ่งหน้า">
    <?php if (! $paginator->onFirstPage()): ?><a href="<?= e($paginator->previousPageUrl()) ?>">← ก่อนหน้า</a><?php endif; ?>
    <span>หน้า <?= e($paginator->currentPage()) ?> / <?= e($paginator->lastPage()) ?> (<?= e($paginator->total()) ?> รายการ)</span>
    <?php if ($paginator->hasMorePages()): ?><a href="<?= e($paginator->nextPageUrl()) ?>">ถัดไป →</a><?php endif; ?>
</nav>
<?php endif; ?>
