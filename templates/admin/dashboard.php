<?php
/**
 * @var App\Core\View $this
 * @var array<string, int> $stats
 * @var list<array<string, mixed>> $recent
 */
?>
<h1><?= $this->e($this->t('admin.dashboard.title')) ?></h1>

<ul class="stats">
    <?php foreach ($stats as $key => $value): ?>
        <li><strong><?= $this->e($value) ?></strong> <?= $this->e($this->t('admin.dashboard.stat.' . $key)) ?></li>
    <?php endforeach; ?>
</ul>

<h2 class="section"><?= $this->e($this->t('admin.dashboard.recent')) ?></h2>
<?php if ($recent === []): ?>
    <p class="muted"><?= $this->e($this->t('admin.common.empty')) ?></p>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr>
                <th scope="col"><?= $this->e($this->t('admin.audit.when')) ?></th>
                <th scope="col"><?= $this->e($this->t('admin.audit.who')) ?></th>
                <th scope="col"><?= $this->e($this->t('admin.audit.action')) ?></th>
                <th scope="col"><?= $this->e($this->t('admin.audit.entity')) ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($recent as $row): ?>
                <tr>
                    <td><?= $this->e($this->datetime((string) $row['occurred_at'])) ?></td>
                    <td><?= $this->e($row['display_name'] ?? $this->t('admin.audit.system')) ?></td>
                    <td><code><?= $this->e($row['action']) ?></code></td>
                    <td><?= $this->e(trim(($row['entity_type'] ?? '') . ' ' . ($row['entity_id'] ?? ''))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
