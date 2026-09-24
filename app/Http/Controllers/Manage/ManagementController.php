<?php

declare(strict_types=1);

namespace App\Http\Controllers\Manage;

use App\Authorization\Gate;
use App\Authorization\ResourceScope;
use App\Domain\Management\EditorialGuard;
use App\Domain\Management\ManagementRepository;
use App\Http\Controller;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\I18n\LocaleRegistry;
use DomainException;

/**
 * Base dei controller di gestione dei contenuti, comuni ad area amministrativa ("admin") e area riservata
 * ("portal"): le rotte hanno lo stesso nome con prefisso diverso (admin.sites.show / portal.sites.show) e
 * gli stessi template in templates/manage. Le autorizzazioni di scrittura stanno nei servizi di dominio;
 * qui si controlla solo la lettura nell'area riservata.
 */
abstract class ManagementController extends Controller
{
    protected const PER_PAGE = 50;

    /** 'admin' oppure 'portal' */
    abstract protected function area(): string;

    /**
     * Lettura di una scheda dell'organizzazione indicata. In admin basta il permesso della rotta
     * (organizations.view_all); nell'area riservata serve l'accesso a quell'organizzazione.
     */
    protected function authorizeOrganization(Request $request, int $organizationId): void
    {
        if ($this->area() === 'portal'
            && !$this->container->get(Gate::class)->allows($this->user($request), 'portal.access', ResourceScope::organization($organizationId))) {
            throw new HttpException(404);
        }
    }

    /**
     * Cosa l'utente può modificare nell'organizzazione, per mostrare solo i moduli utilizzabili
     * (il controllo vero resta nei servizi di dominio).
     *
     * @return array<string, bool>
     */
    protected function capabilities(Request $request, int $organizationId): array
    {
        $guard = $this->container->get(EditorialGuard::class);
        $user = $this->user($request);
        $can = [];
        foreach ([
            'profile' => 'org.profile.edit', 'sites' => 'org.sites.edit', 'services' => 'org.services.edit',
            'mediators' => 'org.mediators.edit', 'translations' => 'translations.edit', 'publish' => 'content.publish',
        ] as $key => $permission) {
            $can[$key] = $guard->allows($user, $permission, $organizationId);
        }
        $can['manage_all'] = $guard->allows($user, 'organizations.manage');
        $can['status'] = $this->area() === 'admin'
            && ($guard->allows($user, 'organizations.verify') || $guard->allows($user, 'organizations.enable'));

        return $can;
    }

    protected function route(string $name): string
    {
        return $this->area() . '.' . $name;
    }

    /** @param array<string, mixed> $data */
    protected function page(string $template, array $data): Response
    {
        return $this->render($template, [...$data, 'area' => $this->area()], $this->area() === 'admin' ? 'layouts/admin' : 'layouts/public');
    }

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

        return $this->redirectTo($this->route($route), $params);
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

    /**
     * @param list<string> $fields
     * @return array<string, ?string> campi di testo indicati, presi dal corpo della richiesta
     */
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

    /** Torna al modulo di creazione con l'errore del dominio. */
    protected function backWithDomainError(Request $request, string $route, array $params, DomainException $e): Response
    {
        return $this->backWithErrors($request, $this->view()->route($this->route($route), $params), ['form' => [$this->t($e->getMessage())]]);
    }
}
