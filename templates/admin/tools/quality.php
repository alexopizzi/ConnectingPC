<?php
/**
 * Cruscotto qualità dei dati (vault "72").
 *
 * @var App\Core\View $this
 * @var array<string, list<array<string, mixed>>> $report
 */
$serviceList = function (array $rows): string {
    $html = '';
    foreach ($rows as $row) {
        $html .= '<li><a href="' . $this->e($this->route('admin.services.show', ['id' => (int) $row['id']])) . '">' . $this->e($row['name']) . '</a>'
            . (isset($row['organization_name']) ? ' <span class="muted">· ' . $this->e($row['organization_name']) . '</span>' : '')
            . (isset($row['next_review_at']) ? ' <span class="badge badge--warning">' . $this->e($row['next_review_at']) . '</span>' : '')
            . (isset($row['locale']) ? ' <span class="badge">' . $this->e(strtoupper((string) $row['locale'])) . '</span>' : '') . '</li>';
    }

    return $html;
};
$sections = [
    'review_due' => 'services',
    'never_verified' => 'services',
    'services_without_sites' => 'services',
    'outdated_translations' => 'services',
    'sites_without_coordinates' => 'sites',
    'organizations_to_census' => 'organizations',
];
?>
<h1><?= $this->e($this->t('admin.quality.title')) ?></h1>
<p class="field__hint"><?= $this->e($this->t('admin.quality.intro')) ?></p>

<h2><?= $this->e($this->t('admin.quality.missing_translations')) ?></h2>
<ul class="badges">
    <?php foreach ($report['missing_translations'] as $row): ?>
        <li class="badge<?= $row['count'] > 0 ? ' badge--warning' : ' badge--success' ?>"><?= $this->e(strtoupper($row['locale'])) ?>: <?= (int) $row['count'] ?></li>
    <?php endforeach; ?>
</ul>

<?php foreach ($sections as $key => $kind):
    $rows = $report[$key]; ?>
    <details class="section"<?= $rows !== [] && $key === 'review_due' ? ' open' : '' ?>>
        <summary><strong><?= $this->e($this->t('admin.quality.' . $key)) ?></strong> (<?= count($rows) ?>)</summary>
        <?php if ($rows === []): ?>
            <p class="muted"><?= $this->e($this->t('admin.common.empty')) ?></p>
        <?php elseif ($kind === 'services'): ?>
            <ul class="plain-list"><?= $serviceList($rows) ?></ul>
        <?php elseif ($kind === 'sites'): ?>
            <ul class="plain-list">
                <?php foreach ($rows as $row): ?>
                    <li><a href="<?= $this->e($this->route('admin.sites.show', ['id' => (int) $row['id']])) ?>"><?= $this->e($row['address_line'] . ', ' . $row['town']) ?></a> <span class="muted">· <?= $this->e($row['organization_name']) ?></span></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <ul class="plain-list">
                <?php foreach ($rows as $row): ?>
                    <li><a href="<?= $this->e($this->route('admin.organizations.show', ['id' => (int) $row['id']])) ?>"><?= $this->e($row['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </details>
<?php endforeach; ?>
