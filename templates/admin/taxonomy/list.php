<?php
/**
 * @var App\Core\View $this
 * @var string $type
 * @var list<array<string, mixed>> $items
 * @var list<string> $locales
 */
?>
<p><a href="<?= $this->e($this->route('admin.taxonomy.index')) ?>"><?= $this->e($this->t('admin.taxonomy.back')) ?></a></p>
<div class="page-actions">
    <h1><?= $this->e($this->t('admin.taxonomy.' . $type)) ?></h1>
    <a class="button" href="<?= $this->e($this->route('admin.taxonomy.create', ['type' => $type])) ?>"><?= $this->e($this->t('admin.taxonomy.create')) ?></a>
</div>
<div class="table-wrap">
    <table class="table">
        <caption><?= $this->e($this->t('admin.common.results', ['count' => count($items)])) ?></caption>
        <thead><tr>
            <th scope="col"><?= $this->e($this->t('admin.taxonomy.label')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.taxonomy.code')) ?></th>
            <?php foreach ($locales as $code): if ($code === 'it') { continue; } ?>
                <th scope="col"><?= $this->e(strtoupper($code)) ?></th>
            <?php endforeach; ?>
            <th scope="col"><?= $this->e($this->t('admin.taxonomy.state')) ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($items as $item):
            $isChild = !empty($item['parent_id']); ?>
            <tr>
                <th scope="row"><?= $isChild ? '<span aria-hidden="true">↳ </span>' : '' ?><?php if (!empty($item['icon'])): ?><span aria-hidden="true"><?= $this->icon((string) $item['icon']) ?></span> <?php endif; ?>
                    <a href="<?= $this->e($this->route('admin.taxonomy.edit', ['type' => $type, 'id' => $item['id']])) ?>"><?= $this->e($item['labels']['it']['text'] ?? $item['code']) ?></a></th>
                <td><code><?= $this->e($item['code']) ?></code></td>
                <?php foreach ($locales as $code): if ($code === 'it') { continue; }
                    $label = $item['labels'][$code] ?? null; ?>
                    <td lang="<?= $this->e($code) ?>" dir="<?= $code === 'ar' ? 'rtl' : 'ltr' ?>"><?= $label === null ? '<span class="badge badge--warning">' . $this->e($this->t('manage.texts.missing')) . '</span>' : $this->e($label['text']) . ($label['status'] !== 'approved' ? ' <span class="badge">' . $this->e($this->t('manage.translation.' . $label['status'])) . '</span>' : '') ?></td>
                <?php endforeach; ?>
                <td><?= array_key_exists('is_active', $item) && !$item['is_active'] ? '<span class="badge badge--warning">' . $this->e($this->t('admin.taxonomy.inactive')) . '</span>' : '' ?>
                    <?= !empty($item['is_featured']) ? '<span class="badge">' . $this->e($this->t('admin.taxonomy.featured')) . '</span>' : '' ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
