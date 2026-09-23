<?php
/**
 * @var App\Core\View $this
 * @var list<array<string, mixed>> $users
 * @var int $total
 * @var int $page
 * @var int $pages
 * @var array{q: string, status: string} $filters
 */
$statuses = ['invited', 'active', 'suspended', 'disabled'];
?>
<div class="page-actions">
    <h1><?= $this->e($this->t('admin.users.title')) ?></h1>
    <a class="button" href="<?= $this->e($this->route('admin.users.create')) ?>"><?= $this->e($this->t('admin.users.create_title')) ?></a>
</div>

<form class="filters" method="get" action="<?= $this->e($this->route('admin.users.index')) ?>">
    <div class="field">
        <label for="f-q"><?= $this->e($this->t('admin.common.search')) ?></label>
        <input id="f-q" name="q" type="search" value="<?= $this->e($filters['q']) ?>">
    </div>
    <div class="field">
        <label for="f-status"><?= $this->e($this->t('admin.users.status')) ?></label>
        <select id="f-status" name="status">
            <option value=""><?= $this->e($this->t('admin.common.all')) ?></option>
            <?php foreach ($statuses as $status): ?>
                <option value="<?= $status ?>"<?= $filters['status'] === $status ? ' selected' : '' ?>><?= $this->e($this->t('admin.users.status.' . $status)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div><button class="button button--secondary" type="submit"><?= $this->e($this->t('admin.common.filter')) ?></button></div>
</form>

<div class="table-wrap">
    <table class="table">
        <caption><?= $this->e($this->t('admin.common.results', ['count' => $total])) ?></caption>
        <thead><tr>
            <th scope="col"><?= $this->e($this->t('admin.users.name')) ?></th>
            <th scope="col"><?= $this->e($this->t('auth.field.email')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.users.status')) ?></th>
            <th scope="col"><?= $this->e($this->t('admin.users.last_login')) ?></th>
        </tr></thead>
        <tbody>
        <?php foreach ($users as $user): ?>
            <tr>
                <th scope="row"><a href="<?= $this->e($this->route('admin.users.show', ['id' => (int) $user['id']])) ?>"><?= $this->e($user['display_name']) ?></a></th>
                <td class="ltr"><?= $this->e($user['email']) ?></td>
                <td><span class="badge"><?= $this->e($this->t('admin.users.status.' . $user['status'])) ?></span></td>
                <td><?= $this->e($this->datetime($user['last_login_at'] ?? null)) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?= $this->partial('pagination', ['page' => $page, 'pages' => $pages, 'route' => 'admin.users.index', 'query' => array_filter($filters)]) ?>
