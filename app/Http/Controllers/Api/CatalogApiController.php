<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Catalog\CatalogFilters;
use App\Domain\Catalog\CatalogRepository;
use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;
use App\I18n\LocaleRegistry;
use App\I18n\Translator;

/**
 * API pubbliche in sola lettura (vault "36 - API"): solo contenuti pubblicati e dati pubblici,
 * nessun cookie, nessuna posizione dell'utente tra i parametri (D-006).
 */
final class CatalogApiController extends Controller
{
    /** GET /api/v1/map/points — sedi con coordinate dei servizi che rispondono ai filtri. */
    public function mapPoints(Request $request): Response
    {
        $locale = $this->locale($request);
        $catalog = $this->container->get(CatalogRepository::class);
        $ids = $catalog->searchServiceIds(CatalogFilters::fromRequest($request), $locale);

        return $this->json(['locale' => $locale, 'points' => $catalog->mapPoints($ids, $locale)]);
    }

    /** GET /api/v1/search/suggest?q= — bisogni e servizi suggeriti durante la digitazione. */
    public function suggest(Request $request): Response
    {
        $locale = $this->locale($request);
        $query = mb_substr($request->string('q'), 0, 100);

        return $this->json([
            'locale' => $locale,
            'suggestions' => $this->container->get(CatalogRepository::class)->suggestions($query, $locale),
        ]);
    }

    /** GET /api/v1/needs — elenco dei bisogni con etichette tradotte. */
    public function needs(Request $request): Response
    {
        $locale = $this->locale($request);

        return $this->json(['locale' => $locale, 'needs' => array_map(static fn (array $n): array => [
            'code' => $n['code'], 'label' => $n['label']['text'], 'icon' => $n['icon'],
        ], $this->container->get(CatalogRepository::class)->needs($locale))]);
    }

    /** GET /api/v1/services — elenco paginato (base per gli open data). */
    public function services(Request $request): Response
    {
        $locale = $this->locale($request);
        $catalog = $this->container->get(CatalogRepository::class);
        $ids = $catalog->searchServiceIds(CatalogFilters::fromRequest($request), $locale);
        $perPage = 50;
        $page = max(1, $request->int('page', 1));

        $items = array_map(static fn (array $s): array => [
            'id' => $s['id'],
            'name' => $s['name']['text'],
            'summary' => $s['summary']['text'] ?? null,
            'organization' => ['id' => $s['organization_id'], 'name' => $s['organization_name']],
            'towns' => $s['towns'],
            'languages' => $s['languages'],
            'cost' => $s['cost_type'],
            'mediation' => $s['mediation'],
            'location' => $s['lat'] === null ? null : ['lat' => $s['lat'], 'lng' => $s['lng']],
        ], $catalog->serviceSummaries(array_slice($ids, ($page - 1) * $perPage, $perPage), $locale));

        return $this->json(['locale' => $locale, 'total' => count($ids), 'page' => $page, 'per_page' => $perPage, 'items' => $items]);
    }

    private function locale(Request $request): string
    {
        $locales = $this->container->get(LocaleRegistry::class);
        $locale = $request->string('locale');
        $locale = $locales->isPublic($locale) ? $locale : $locales->default();
        $this->container->get(Translator::class)->setLocale($locale);

        return $locale;
    }

    /** @param array<string, mixed> $data */
    private function json(array $data): Response
    {
        return Response::json($data)
            ->withHeader('Cache-Control', 'public, max-age=300')
            ->withHeader('Access-Control-Allow-Origin', '*');
    }
}
