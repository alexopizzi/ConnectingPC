<?php
/**
 * @var App\Core\View $this
 * @var string $current
 */
// Stati intermedi (approvato, respinto) si mostrano come "in revisione"
$current = in_array($current, App\Domain\Management\EditorialGuard::PUBLICATION_STATUSES, true) ? $current : 'in_review';
?>
<div class="field">
    <label for="f-publication"><?= $this->e($this->t('manage.publication')) ?></label>
    <p class="field__hint" id="f-publication-hint"><?= $this->e($this->t('manage.publication.hint')) ?></p>
    <select id="f-publication" name="publication_status" aria-describedby="f-publication-hint">
        <?php foreach (App\Domain\Management\EditorialGuard::PUBLICATION_STATUSES as $status): ?>
            <option value="<?= $status ?>"<?= $current === $status ? ' selected' : '' ?>><?= $this->e($this->t('manage.publication.' . $status)) ?></option>
        <?php endforeach; ?>
    </select>
</div>
