<?php
/**
 * @var App\Core\View $this
 * @var list<array<string, mixed>> $roles
 * @var list<array<string, mixed>> $permissions
 * @var array<int, array<int, true>> $matrix
 */
?>
<h1><?= $this->e($this->t('admin.roles.title')) ?></h1>
<p><?= $this->e($this->t('admin.roles.intro')) ?></p>

<div class="table-wrap">
    <table class="table table--matrix">
        <caption><?= $this->e($this->t('admin.roles.caption')) ?></caption>
        <thead>
        <tr>
            <th scope="col"><?= $this->e($this->t('admin.roles.permission')) ?></th>
            <?php foreach ($roles as $role): ?>
                <th scope="col" title="<?= $this->e($role['description']) ?>"><?= $this->e($role['name']) ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($permissions as $permission): ?>
            <tr>
                <th scope="row"><code><?= $this->e($permission['code']) ?></code><br><span class="muted"><?= $this->e($permission['description']) ?></span></th>
                <?php foreach ($roles as $role):
                    $has = isset($matrix[(int) $role['id']][(int) $permission['id']]); ?>
                    <td><?= $has ? '✔<span class="visually-hidden"> ' . $this->e($this->t('admin.common.yes')) . '</span>' : '<span class="visually-hidden">' . $this->e($this->t('admin.common.no')) . '</span>' ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
