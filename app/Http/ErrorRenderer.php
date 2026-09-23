<?php

declare(strict_types=1);

namespace App\Http;

use App\Core\Container;
use App\Core\Logger;
use App\Core\View;
use App\I18n\LocaleRegistry;
use App\I18n\Translator;
use Throwable;

/**
 * Pagine di errore localizzate. Non deve mai fallire: in ultima istanza restituisce testo semplice.
 */
final class ErrorRenderer
{
    public function __construct(private readonly Container $container)
    {
    }

    public function render(Request $request, int $status, ?Throwable $debug = null): Response
    {
        $requestId = $this->container->get(Logger::class)->requestId();
        // 419 (pagina scaduta, CSRF) non è un codice standard: Apache lo trasforma in 500 (P-009).
        // Si mostra il testo specifico ma si risponde 403.
        $httpStatus = $status === 419 ? 403 : $status;

        if (str_starts_with($request->path, '/api/') || $request->path === '/health') {
            return Response::json(['error' => ['code' => $status, 'request_id' => $requestId]], $httpStatus);
        }

        try {
            $locales = $this->container->get(LocaleRegistry::class);
            $locale = $request->attribute('locale');
            if (!is_string($locale) || !$locales->isEnabled($locale)) {
                $locale = str_starts_with($request->path, '/admin')
                    ? $locales->default()
                    : $locales->negotiate($request->acceptedLanguages());
            }
            $this->container->get(Translator::class)->setLocale($locale);

            $view = $this->container->get(View::class);
            $known = in_array($status, [400, 403, 404, 405, 419, 429, 500, 503], true) ? $status : 500;
            $html = $view->render('errors/error', [
                'status' => $known,
                'requestId' => $requestId,
                'debug' => $debug,
                'pageTitle' => $view->t('error.' . $known . '.title'),
            ], 'layouts/public');

            return Response::html($html, $httpStatus);
        } catch (Throwable $e) {
            $this->container->get(Logger::class)->error('Errore nel rendering della pagina di errore', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return new Response('Error ' . $status . ' — ' . $requestId, $httpStatus, ['Content-Type' => 'text/plain; charset=utf-8']);
        }
    }
}
