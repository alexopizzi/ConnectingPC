<?php
/**
 * Recapiti pubblici con azione diretta (chiama, scrivi, sito). Numeri ed email sempre da sinistra a destra.
 *
 * @var App\Core\View $this
 * @var list<array{kind: string, value: string, label_key: ?string}> $contacts
 */
if ($contacts === []) {
    return;
}
?>
<ul class="contact-list">
    <?php foreach ($contacts as $contact):
        $value = $contact['value'];
        [$href, $icon, $label] = match ($contact['kind']) {
            'phone', 'mobile' => ['tel:' . preg_replace('/[^0-9+]/', '', $value), '📞', 'catalog.contact.call'],
            'whatsapp' => ['https://wa.me/' . preg_replace('/[^0-9]/', '', $value), '💬', 'catalog.contact.whatsapp'],
            'email', 'pec' => ['mailto:' . $value, '✉️', 'catalog.contact.write'],
            'website', 'social' => [$value, '🌐', 'catalog.contact.website'],
            default => [null, '•', 'catalog.contact.other'],
        };
        $safeHref = $href !== null && preg_match('#^(tel:|mailto:|https?://)#', $href) ? $href : null; ?>
        <li>
            <span aria-hidden="true"><?= $icon ?></span>
            <?= $this->e($this->t($label)) ?>:
            <?php if ($safeHref !== null): ?>
                <a class="ltr" href="<?= $this->e($safeHref) ?>"<?= str_starts_with($safeHref, 'http') ? ' rel="noopener noreferrer" target="_blank"' : '' ?>><?= $this->e($value) ?></a>
            <?php else: ?>
                <span class="ltr"><?= $this->e($value) ?></span>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</ul>
