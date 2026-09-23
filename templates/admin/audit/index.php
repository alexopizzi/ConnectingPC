<?php
/**
 * @var App\Core\View $this
 * @var list<array<string, mixed>> $rows
 * @var int $total
 * @var int $page
 * @var int $pages
 * @var array<string, string> $filters
 */
?>
<h1><?= $this->e($this->t('admin.audit.title')) ?></h1>

<form class="filters" method="get" action="<?= $this->e($this->route('admin.audit.index')) ?>">
    <?php foreach (['action' => 'admin.audit.action', 'entity_type' => 'admin.audit.entity', 'entity_id' => 'admin.audit.entity_id', 'user_id' => 'admin.audit.user_id'] as $name => $label): ?>
        <div class="field">
            <label for="f-<?= $name ?>"><?= $this->e($this->t($label)) ?></label>
            <input id="f-<?= $name ?>" name="<?= $name ?>" value="<?= $this->e($filters[$name]) ?>">
        </div>
    <?php endforeach; ?>
    <div><button class="button button--secondary" type="submit"><?= $this->e($this->t('admin.common.filter')) ?></button></div>
</form>

<div class="table-wrap">
    <table class="table">
        <caption><?= $this->e($this->t('admin.common.results', ['count' => $total])) ?></caption>
        <thead><tr>
            <th scope="col"><?= $this->e($this->t('admin.audit.when')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.audit.who')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.audit.action')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.audit.entity')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.audit.changes')) ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= $this->e($this->datetime((string) $row['occurred_at'])) ?><br><small class="muted ltr"><?= $this->e($row['request_id'] ?? '') ?></small></td>
                <td>
                    <?= $this->e($row['user_name'] ?? $this->t('admin.audit.system')) ?>
                    <?php if ($row['organization_name'] !== null): ?><br><small class="muted"><?= $this->e($row['organization_name']) ?></small><?php endif; ?>
                    <?php if ($row['approver_name'] !== null): ?><br><small><?= $this->e($this->t('admin.audit.approved_by', ['name' => (string) $row['approver_name']])) ?></small><?php endif; ?>
                </td>
                <td><code><?= $this->e($row['action']) ?></code></td>
                <td><?= $this->e(trim(($row['entity_type'] ?? '') . ' ' . ($row['entity_id'] ?? ''))) ?></td>
                <td>
                    <?php if ($row['changes'] !== null): ?>
                        <details>
                            <summary><?= $this->e($this->t('admin.audit.show_changes')) ?></summary>
                            <pre class="ltr"><?= $this->e(json_encode(json_decode((string) $row['changes'], true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
                        </details>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->partial('pagination', ['page' => $page, 'pages' => $pages, 'route' => 'admin.audit.index', 'query' => array_filter($filters)]) ?>
