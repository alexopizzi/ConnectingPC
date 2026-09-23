<?php
/**
 * Pagina tecnica dello scheletro v0.1.0 (sostituita dal layout pubblico in v0.2.0).
 * Eccezione temporanea alla regola "nessuna stringa hardcoded" (vedi AGENTS.md).
 *
 * @var list<array{check: string, status: string, detail: string}> $checks
 */

use App\Core\App;

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$labels = ['ok' => 'OK', 'warn' => 'Attenzione', 'fail' => 'Errore'];
$isNotFound = http_response_code() === 404;
?>
<!doctype html>
<html lang="it" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= $isNotFound ? 'Pagina non trovata — ' : '' ?>ConnectingPC</title>
    <link rel="stylesheet" href="<?= $e(App::baseUrl()) ?>/assets/css/system.css">
</head>
<body>
<main class="system">
    <h1>ConnectingPC</h1>
    <?php if ($isNotFound): ?>
        <p>La pagina richiesta non esiste.</p>
    <?php else: ?>
        <p>La piattaforma è in costruzione.</p>
    <?php endif; ?>
    <p class="version">Versione <?= $e(App::version()) ?></p>

    <?php if ($checks !== []): ?>
        <h2>Verifica dell'ambiente</h2>
        <table>
            <caption>Requisiti di esecuzione (visibile solo in ambiente locale)</caption>
            <thead>
            <tr><th scope="col">Controllo</th><th scope="col">Esito</th><th scope="col">Dettaglio</th></tr>
            </thead>
            <tbody>
            <?php foreach ($checks as $row): ?>
                <tr class="is-<?= $e($row['status']) ?>">
                    <th scope="row"><?= $e($row['check']) ?></th>
                    <td><?= $e($labels[$row['status']] ?? $row['status']) ?></td>
                    <td><?= $e($row['detail']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
</body>
</html>
