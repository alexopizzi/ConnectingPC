<?php
/**
 * Pagina di manutenzione (D-041).
 *
 * @var App\Core\View $this
 * @var string $version
 * @var string $environment
 * @var list<array{check: string, status: string, detail: string}> $checks
 * @var list<array{version: string, applied_at: ?string, checksum_ok: ?bool}>|null $migrations
 * @var string|null $dbError
 * @var bool|null $superAdmin
 * @var bool $down
 * @var bool $demoAllowed
 * @var list<string> $log
 * @var string|null $activationLink
 * @var int $expiresAt
 */
$statusBadge = [App\Core\EnvironmentCheck::OK => 'badge--success', App\Core\EnvironmentCheck::WARN => 'badge--warning', App\Core\EnvironmentCheck::FAIL => 'badge--danger'];
$action = function (string $name, string $label, string $class = 'button', string $icon = 'check'): string {
    return '<form method="post" action="' . $this->e($this->route('system.maintenance.action')) . '" class="inline-form">'
        . $this->csrfField() . '<input type="hidden" name="action" value="' . $this->e($name) . '">'
        . '<button class="' . $this->e($class) . '" type="submit">' . $this->icon($icon) . $this->e($label) . '</button></form>';
};
?>
<div class="container section">
    <h1><?= $this->e($this->t('admin.maintenance.title')) ?></h1>
    <p class="lead"><?= $this->e($this->t('admin.maintenance.version', ['version' => $version, 'environment' => $environment])) ?>
        · <?= $this->e($this->t('admin.maintenance.session_until', ['time' => (new DateTimeImmutable('@' . $expiresAt))->setTimezone(new DateTimeZone('Europe/Rome'))->format('H:i')])) ?></p>
    <p class="alert <?= $down ? 'alert--warning' : 'alert--success' ?>"><?= $this->e($this->t($down ? 'admin.maintenance.state_down' : 'admin.maintenance.state_up')) ?></p>

    <?php if ($activationLink !== null): ?>
        <div class="alert alert--warning" role="alert">
            <?= $this->icon('triangle-alert') ?>
            <div>
                <p><strong><?= $this->e($this->t('admin.maintenance.admin_link')) ?></strong></p>
                <p class="ltr"><a href="<?= $this->e($activationLink) ?>"><?= $this->e($activationLink) ?></a></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($log !== []): ?>
        <section class="service-section" aria-labelledby="log-title">
            <h2 id="log-title"><?= $this->e($this->t('admin.maintenance.log')) ?></h2>
            <pre class="diff"><?= $this->e(implode("\n", $log)) ?></pre>
        </section>
    <?php endif; ?>

    <section class="service-section" aria-labelledby="db-title">
        <h2 id="db-title"><?= $this->icon('layers') ?><?= $this->e($this->t('admin.maintenance.database')) ?></h2>
        <?php if ($dbError !== null): ?>
            <p class="alert alert--error"><?= $this->e($this->t('admin.maintenance.db_error')) ?></p>
            <pre class="diff"><?= $this->e($dbError) ?></pre>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th scope="col"><?= $this->e($this->t('admin.maintenance.migration')) ?></th><th scope="col"><?= $this->e($this->t('admin.maintenance.result')) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($migrations as $row): ?>
                        <tr>
                            <th scope="row"><code><?= $this->e($row['version']) ?></code></th>
                            <td>
                                <?php if ($row['applied_at'] === null): ?>
                                    <span class="badge badge--warning"><?= $this->e($this->t('admin.maintenance.pending')) ?></span>
                                <?php else: ?>
                                    <span class="badge badge--success"><?= $this->e($this->t('admin.maintenance.applied', ['date' => $row['applied_at']])) ?></span>
                                    <?php if ($row['checksum_ok'] === false): ?>
                                        <span class="badge badge--danger"><?= $this->e($this->t('admin.maintenance.changed')) ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <p class="field__hint"><?= $this->e($this->t('admin.maintenance.install_hint')) ?></p>
        <p><?= $action('install', $this->t('admin.maintenance.install'), 'button', 'rotate-ccw') ?></p>
    </section>

    <?php if ($demoAllowed && $dbError === null): ?>
        <section class="service-section" aria-labelledby="demo-title">
            <h2 id="demo-title"><?= $this->icon('layers') ?><?= $this->e($this->t('admin.maintenance.demo_title')) ?></h2>
            <p class="alert alert--warning"><?= $this->e($this->t('admin.maintenance.demo_hint')) ?></p>
            <p><?= $action('demo', $this->t('admin.maintenance.demo'), 'button button--secondary', 'layers') ?></p>
        </section>
    <?php endif; ?>

    <?php if ($superAdmin === false): ?>
        <section class="service-section" aria-labelledby="admin-title">
            <h2 id="admin-title"><?= $this->icon('user-round') ?><?= $this->e($this->t('admin.maintenance.admin_title')) ?></h2>
            <p class="field__hint"><?= $this->e($this->t('admin.maintenance.admin_hint')) ?></p>
            <form class="form" method="post" action="<?= $this->e($this->route('system.maintenance.action')) ?>">
                <?= $this->csrfField() ?>
                <input type="hidden" name="action" value="create_admin">
                <div class="field"><label for="m-email"><?= $this->e($this->t('admin.maintenance.email')) ?></label>
                    <input id="m-email" name="email" type="email" autocomplete="email" required></div>
                <div class="field"><label for="m-name"><?= $this->e($this->t('admin.maintenance.name')) ?></label>
                    <input id="m-name" name="name" type="text" autocomplete="name" maxlength="150" required></div>
                <div><button class="button" type="submit"><?= $this->icon('check') ?><?= $this->e($this->t('admin.maintenance.create')) ?></button></div>
            </form>
        </section>
    <?php endif; ?>

    <section class="service-section" aria-labelledby="tools-title">
        <h2 id="tools-title"><?= $this->icon('sliders-horizontal') ?><?= $this->e($this->t('admin.maintenance.tools')) ?></h2>
        <div class="page-actions">
            <?= $action('cache', $this->t('admin.maintenance.cache'), 'button button--secondary', 'rotate-ccw') ?>
            <?= $down
                ? $action('up', $this->t('admin.maintenance.up'), 'button button--success', 'circle-check')
                : $action('down', $this->t('admin.maintenance.down'), 'button button--ghost', 'triangle-alert') ?>
            <?= $action('logout', $this->t('admin.maintenance.logout'), 'button button--ghost', 'x') ?>
        </div>
    </section>

    <section class="service-section" aria-labelledby="checks-title">
        <h2 id="checks-title"><?= $this->icon('circle-check') ?><?= $this->e($this->t('admin.maintenance.checks')) ?></h2>
        <div class="table-wrap">
            <table class="table">
                <thead><tr>
                    <th scope="col"><?= $this->e($this->t('admin.maintenance.check')) ?></th>
                    <th scope="col"><?= $this->e($this->t('admin.maintenance.result')) ?></th>
                    <th scope="col"><?= $this->e($this->t('admin.maintenance.detail')) ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($checks as $row): ?>
                    <tr>
                        <th scope="row"><?= $this->e($row['check']) ?></th>
                        <td><span class="badge <?= $statusBadge[$row['status']] ?? '' ?>"><?= $this->e(strtoupper((string) $row['status'])) ?></span></td>
                        <td><?= $this->e($row['detail']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
