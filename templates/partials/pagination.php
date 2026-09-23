<?php
/**
 * @var App\Core\View $this
 * @var int $page
 * @var int $pages
 * @var string $route
 * @var array<string, string|int> $query
 */
if ($pages <= 1) {
    return;
}
?>
<nav class="pagination" aria-label="<?= $this->e($this->t('pagination.label')) ?>">
    <?php if ($page > 1): ?>
        <a class="button button--secondary" href="<?= $this->e($this->route($route, [...$query, 'page' => $page - 1])) ?>" rel="prev"><?= $this->e($this->t('pagination.previous')) ?></a>
    <?php endif; ?>
    <span><?= $this->e($this->t('pagination.status', ['page' => $page, 'pages' => $pages])) ?></span>
    <?php if ($page < $pages): ?>
        <a class="button button--secondary" href="<?= $this->e($this->route($route, [...$query, 'page' => $page + 1])) ?>" rel="next"><?= $this->e($this->t('pagination.next')) ?></a>
    <?php endif; ?>
</nav>
