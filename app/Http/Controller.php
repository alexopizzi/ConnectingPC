<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\Container;
use App\Core\Database;
use App\Core\Session;
use App\Core\View;

/**
 * Base dei controller: nessuna query e nessuna logica di dominio qui, solo coordinamento
 * fra richiesta, autorizzazione, servizi e template (vault "30 - Architettura applicativa").
 */
abstract class Controller
{
    public function __construct(protected readonly Container $container)
    {
    }

    /** @param array<string, mixed> $data */
    protected function render(string $template, array $data = [], string $layout = 'layouts/public', int $status = 200): Response
    {
        return Response::html($this->view()->render($template, $data, $layout), $status);
    }

    /** @param array<string, string|int> $params */
    protected function redirectTo(string $routeName, array $params = []): Response
    {
        return Response::redirect($this->view()->route($routeName, $params));
    }

    /** @param array<string, string|int|float> $params */
    protected function t(string $key, array $params = []): string
    {
        return $this->view()->t($key, $params);
    }

    /** Messaggio da mostrare dopo il redirect (tipo: success, info, warning, error). */
    protected function flash(string $type, string $message): void
    {
        $this->container->get(Session::class)->flashPush('_messages', ['type' => $type, 'message' => $message]);
    }

    /**
     * Torna al modulo con errori e valori inseriti (esclusi i campi sensibili).
     *
     * @param array<string, list<string>> $errors
     * @param list<string> $except
     */
    protected function backWithErrors(Request $request, string $url, array $errors, array $except = []): Response
    {
        $session = $this->container->get(Session::class);
        $sensitive = [...$except, 'password', 'password_confirmation', 'current_password', '_token'];
        $session->flash('_errors', $errors);
        $session->flash('_old', array_diff_key(array_filter($request->body, 'is_scalar'), array_flip($sensitive)));

        return Response::redirect($url);
    }

    /**
     * Traduce gli errori del Validator (o liste di chiavi) nella lingua corrente.
     *
     * @param array<string, list<array{key: string, params: array<string, string|int>}|string>> $errors
     * @return array<string, list<string>>
     */
    protected function translateErrors(array $errors): array
    {
        $translated = [];
        foreach ($errors as $field => $list) {
            foreach ($list as $error) {
                $translated[$field][] = is_string($error) ? $this->t($error) : $this->t($error['key'], $error['params']);
            }
        }

        return $translated;
    }

    /** @return array<string, mixed>|null */
    protected function user(Request $request): ?array
    {
        $user = $request->attribute('user');

        return is_array($user) ? $user : null;
    }

    protected function view(): View
    {
        return $this->container->get(View::class);
    }

    protected function db(): Database
    {
        return $this->container->get(Database::class);
    }
}
