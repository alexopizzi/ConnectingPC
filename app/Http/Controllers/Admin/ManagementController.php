<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Management\ManagementRepository;
use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;
use App\I18n\LocaleRegistry;
use DomainException;

/**
 * Base dei controller di gestione dei contenuti: lettura dei campi multipli dei moduli ed esecuzione
 * delle operazioni di dominio con messaggio di esito. Le autorizzazioni restano nei servizi di dominio.
 */
abstract class ManagementController extends Controller
{
    protected const PER_PAGE = 50;

    /**
     * Esegue l'operazione e torna alla pagina indicata con un messaggio di esito.
     *
     * @param callable(): (mixed) $operation se restituisce una stringa manage.warning.*, la mostra come avviso
     * @param array<string, string|int> $params
     */
    protected function attempt(callable $operation, string $successKey, string $route, array $params = []): Response
    {
        try {
            $result = $operation();
            $this->flash('success', $this->t($successKey));
            if (is_string($result) && str_starts_with($result, 'manage.warning.')) {
                $this->flash('warning', $this->t($result));
            }
        } catch (DomainException $e) {
            $this->flash('error', $this->t($e->getMessage()));
        }

        return $this->redirectTo($route, $params);
    }

    /**
     * Righe di un campo multiplo del modulo (es. contacts[0][kind]).
     *
     * @return list<array<string, string>>
     */
    protected function rows(Request $request, string $key): array
    {
        $rows = [];
        foreach ((array) ($request->body[$key] ?? []) as $row) {
            if (is_array($row)) {
                $rows[] = array_map(static fn ($v): string => is_scalar($v) ? trim((string) $v) : '', $row);
            }
        }

        return $rows;
    }

    /** @return list<string> valori di un gruppo di caselle (es. languages[]) */
    protected function values(Request $request, string $key): array
    {
        return array_values(array_map('strval', array_filter((array) ($request->body[$key] ?? []), 'is_scalar')));
    }

    /** @return list<int> */
    protected function ids(Request $request, string $key): array
    {
        return array_values(array_filter(array_map('intval', $this->values($request, $key)), static fn (int $v): bool => $v > 0));
    }

    /** @return array<string, ?string> campi di testo indicati, presi dal corpo della richiesta */
    protected function texts(Request $request, array $fields): array
    {
        $texts = [];
        foreach ($fields as $field) {
            $value = $request->body[$field] ?? null;
            $texts[$field] = is_string($value) ? str_replace("\r\n", "\n", trim($value)) : null;
        }

        return $texts;
    }

    /** Lingua dei testi scelta nella pagina (?lingua=), tra quelle attive; altrimenti la lingua sorgente. */
    protected function textLocale(Request $request, string $source): string
    {
        $locale = $request->string('lingua');

        return $this->container->get(LocaleRegistry::class)->isEnabled($locale) ? $locale : $source;
    }

    /** @return list<string> lingue in cui si possono scrivere i contenuti */
    protected function contentLocales(): array
    {
        return array_keys($this->container->get(LocaleRegistry::class)->enabled());
    }

    protected function management(): ManagementRepository
    {
        return $this->container->get(ManagementRepository::class);
    }

    /** @param array<string, mixed> $data */
    protected function page(string $template, array $data): Response
    {
        return $this->render($template, $data, 'layouts/admin');
    }
}
