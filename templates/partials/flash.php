<?php
/** @var App\Core\View $this */
$messages = $this->flashes();
if ($messages === []) {
    return;
}
?>
<div class="flash-messages container">
    <?php foreach ($messages as $message):
        $type = in_array($message['type'], ['success', 'info', 'warning', 'error'], true) ? $message['type'] : 'info'; ?>
        <div class="alert alert--<?= $this->e($type) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>">
            <?= $this->e($message['message']) ?>
        </div>
    <?php endforeach; ?>
</div>
