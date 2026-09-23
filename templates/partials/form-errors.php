<?php
/**
 * Riepilogo degli errori del modulo, in cima (WCAG 3.3.1): annunciato dagli screen reader.
 *
 * @var App\Core\View $this
 * @var list<string> $fields campi di cui mostrare gli errori nel riepilogo
 */
$messages = [];
foreach (['form', ...($fields ?? [])] as $field) {
    foreach ($this->errors($field) as $message) {
        $messages[] = $message;
    }
}
if ($messages === []) {
    return;
}
?>
<div class="alert alert--error" role="alert" tabindex="-1">
    <p><strong><?= $this->e($this->t('form.errors_title')) ?></strong></p>
    <ul>
        <?php foreach (array_unique($messages) as $message): ?>
            <li><?= $this->e($message) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
