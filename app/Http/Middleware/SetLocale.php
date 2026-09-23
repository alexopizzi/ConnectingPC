<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Core\Container;
use App\Core\View;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\I18n\LocaleRegistry;
use App\I18n\Translator;

/**
 * Lingua della richiesta: dal parametro {locale} del percorso oppure fissata ("locale:it" per l'area admin).
 */
final class SetLocale implements Middleware
{
    public function __construct(private readonly Container $container)
    {
    }

    public function handle(Request $request, callable $next, array $arguments = []): Response
    {
        $locales = $this->container->get(LocaleRegistry::class);
        $locale = $arguments[0] ?? $request->attribute('locale');

        if (!is_string($locale) || !$locales->isEnabled($locale)) {
            throw new HttpException(404);
        }
        if ($arguments === [] && !$locales->isPublic($locale)) {
            throw new HttpException(404);
        }

        $request->attributes['locale'] = $locale;
        $this->container->get(Translator::class)->setLocale($locale);
        $this->container->get(View::class)->share('locale', $locale);

        return $next($request)->withHeader('Content-Language', $locale);
    }
}
