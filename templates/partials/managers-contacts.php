<?php
/**
 * Recapiti ufficiali dei gestori (impostazione contacts.managers, Q-02).
 *
 * @var App\Core\View $this
 * @var array<string, string> $managers
 */
$contacts = [];
if (!empty($managers['email'])) {
    $contacts[] = ['kind' => 'email', 'value' => (string) $managers['email'], 'label_key' => null];
}
if (!empty($managers['phone'])) {
    $contacts[] = ['kind' => 'phone', 'value' => (string) $managers['phone'], 'label_key' => null];
}
?>
<div class="callout">
    <h2><?= $this->e($this->t('page.managers.title')) ?></h2>
    <?php if (!empty($managers['name'])): ?><p><strong><?= $this->e($managers['name']) ?></strong></p><?php endif; ?>
    <?= $this->partial('contact-list', ['contacts' => $contacts]) ?>
    <?php if (!empty($managers['hours'])): ?><p><?= $this->e($this->t('page.managers.hours')) ?>: <?= $this->e($managers['hours']) ?></p><?php endif; ?>
    <?php if (!empty($managers['address'])): ?><p><?= $this->e($managers['address']) ?></p><?php endif; ?>
    <p class="muted"><?= $this->e($this->t('page.provisional')) ?></p>
</div>
