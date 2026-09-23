<?php

declare(strict_types=1);

/*
 * Front controller: unico file PHP esposto (vault "30 - Architettura applicativa").
 */

/** @var App\Core\Container $container */
$container = require dirname(__DIR__) . '/app/bootstrap.php';

$request = App\Http\Request::fromGlobals();
(new App\Http\Kernel($container))->handle($request)->send();
