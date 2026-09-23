<?php
/**
 * @var App\Core\View $this
 * @var int $status
 * @var string $requestId
 * @var Throwable|null $debug
 */
?>
<div class="container section error-page">
    <h1><?= $this->e($this->t('error.' . $status . '.title')) ?></h1>
    <p><?= $this->e($this->t('error.' . $status . '.text')) ?></p>
    <p class="muted"><?= $this->e($this->t('error.request_id', ['id' => $requestId])) ?></p>
    <p><a class="button" href="<?= $this->e($this->route('public.home')) ?>"><?= $this->e($this->t('common.back_home')) ?></a></p>

    <?php if ($debug !== null): ?>
        <details class="debug" open>
            <summary>Debug (APP_DEBUG)</summary>
            <pre dir="ltr" lang="en"><?= $this->e($debug::class . ': ' . $debug->getMessage() . "\n" . $debug->getFile() . ':' . $debug->getLine() . "\n\n" . $debug->getTraceAsString()) ?></pre>
        </details>
    <?php endif; ?>
</div>
