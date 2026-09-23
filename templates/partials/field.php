<?php
/**
 * Campo di modulo accessibile: etichetta, suggerimento, errore collegati con aria-describedby.
 *
 * @var App\Core\View $this
 * @var string $name
 * @var string $label
 * @var string|null $type
 * @var string|null $value
 * @var string|null $hint
 * @var string|null $autocomplete
 * @var bool|null $required
 */
$type ??= 'text';
$id = 'f-' . preg_replace('/[^a-z0-9_-]/i', '-', $name);
$errors = $this->errors($name);
$describedBy = trim((isset($hint) && $hint !== '' ? $id . '-hint ' : '') . ($errors !== [] ? $id . '-error' : ''));
$isSecret = $type === 'password';
?>
<div class="field<?= $errors !== [] ? ' field--invalid' : '' ?>">
    <label for="<?= $this->e($id) ?>"><?= $this->e($label) ?></label>
    <?php if (isset($hint) && $hint !== ''): ?>
        <p class="field__hint" id="<?= $this->e($id) ?>-hint"><?= $this->e($hint) ?></p>
    <?php endif; ?>
    <?php if ($errors !== []): ?>
        <p class="field__error" id="<?= $this->e($id) ?>-error"><?= $this->e(implode(' ', $errors)) ?></p>
    <?php endif; ?>
    <input id="<?= $this->e($id) ?>" name="<?= $this->e($name) ?>" type="<?= $this->e($type) ?>"
           value="<?= $isSecret ? '' : $this->e($value ?? $this->old($name)) ?>"
           <?= isset($autocomplete) ? 'autocomplete="' . $this->e($autocomplete) . '"' : '' ?>
           <?= !empty($required) ? 'required aria-required="true"' : '' ?>
           <?= $errors !== [] ? 'aria-invalid="true"' : '' ?>
           <?= $describedBy !== '' ? 'aria-describedby="' . $this->e($describedBy) . '"' : '' ?>>
</div>
