<?php
/** @var App\Core\View $this */
?>
<div class="container section auth-page">
    <h1><?= $this->e($this->t('admin.maintenance.title')) ?></h1>
    <p><?= $this->e($this->t('admin.maintenance.intro')) ?></p>
    <form class="form" method="post" action="<?= $this->e($this->route('system.maintenance.login')) ?>">
        <?= $this->csrfField() ?>
        <div class="field">
            <label for="m-token"><?= $this->e($this->t('admin.maintenance.token')) ?></label>
            <input id="m-token" name="token" type="password" autocomplete="off" required>
        </div>
        <div><button class="button" type="submit"><?= $this->icon('log-in') ?><?= $this->e($this->t('admin.maintenance.enter')) ?></button></div>
    </form>
</div>
