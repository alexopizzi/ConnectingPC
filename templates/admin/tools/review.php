<?php
/**
 * Coda di revisione (RF-35): contenuti in revisione (approva / respingi con nota) e traduzioni da revisionare.
 *
 * @var App\Core\View $this
 * @var list<array<string, mixed>> $content
 * @var list<array<string, mixed>> $translations
 */
$routes = ['organization' => 'admin.organizations.show', 'site' => 'admin.sites.show', 'service' => 'admin.services.show', 'mediator' => 'admin.mediators.show'];
?>
<h1><?= $this->e($this->t('admin.review.title')) ?></h1>
<p class="field__hint"><?= $this->e($this->t('admin.review.intro')) ?></p>

<h2><?= $this->e($this->t('admin.review.content', ['count' => count($content)])) ?></h2>
<?php if ($content === []): ?>
    <p class="muted"><?= $this->e($this->t('admin.common.empty')) ?></p>
<?php else: ?>
    <ul class="card-list">
        <?php foreach ($content as $item): ?>
            <li class="card">
                <h3 class="service-card__title">
                    <a href="<?= $this->e($this->route($routes[$item['entity_type']], ['id' => $item['entity_id']])) ?>"><?= $this->e($item['label']) ?></a>
                    <span class="badge"><?= $this->e($this->t('admin.entity.' . $item['entity_type'])) ?></span>
                </h3>
                <p class="muted"><?= $this->e($item['organization_name'] ?? '—') ?> · <?= $this->e($this->datetime($item['updated_at']) ?: '—') ?></p>
                <form class="filters" method="post" action="<?= $this->e($this->route('admin.review.decide', ['type' => $item['entity_type'], 'id' => $item['entity_id']])) ?>">
                    <?= $this->csrfField() ?>
                    <div class="field">
                        <label for="note-<?= $item['entity_type'] . $item['entity_id'] ?>"><?= $this->e($this->t('admin.review.note')) ?></label>
                        <input id="note-<?= $item['entity_type'] . $item['entity_id'] ?>" type="text" name="note" maxlength="1000">
                    </div>
                    <div class="page-actions">
                        <button class="button" type="submit" name="decision" value="approved"><?= $this->e($this->t('admin.review.approve')) ?></button>
                        <button class="button button--secondary" type="submit" name="decision" value="rejected"><?= $this->e($this->t('admin.review.reject')) ?></button>
                    </div>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<h2><?= $this->e($this->t('admin.review.translations', ['count' => count($translations)])) ?></h2>
<?php if ($translations === []): ?>
    <p class="muted"><?= $this->e($this->t('admin.common.empty')) ?></p>
<?php else: ?>
    <div class="table-wrap">
        <table class="table">
            <thead><tr>
                <th scope="col"><?= $this->e($this->t('admin.review.item')) ?></th>
                <th scope="col"><?= $this->e($this->t('manage.field.language')) ?></th>
                <th scope="col"><?= $this->e($this->t('manage.field.organization')) ?></th>
                <th scope="col"><?= $this->e($this->t('admin.common.actions')) ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($translations as $item): ?>
                <tr>
                    <td><a href="<?= $this->e($this->route($routes[$item['entity_type']], ['id' => $item['entity_id'], 'lingua' => $item['locale']])) ?>#testi"><?= $this->e($item['label']) ?></a>
                        <span class="badge"><?= $this->e($this->t('admin.entity.' . $item['entity_type'])) ?></span></td>
                    <td><?= $this->e(strtoupper($item['locale'])) ?></td>
                    <td><?= $this->e($item['organization_name'] ?? '—') ?></td>
                    <td>
                        <form class="inline-form" method="post" action="<?= $this->e($this->route('admin.review.translation', ['type' => $item['entity_type'], 'id' => $item['entity_id']])) ?>">
                            <?= $this->csrfField() ?>
                            <input type="hidden" name="locale" value="<?= $this->e($item['locale']) ?>">
                            <button class="button button--link" type="submit"><?= $this->e($this->t('admin.review.approve_translation')) ?></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
