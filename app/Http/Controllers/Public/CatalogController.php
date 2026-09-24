<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Core\Env;
use App\Domain\Catalog\CatalogFilters;
use App\Domain\Catalog\CatalogRepository;
use App\Domain\Communities\CommunityRepository;
use App\Http\Controller;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

/**
 * Catalogo pubblico: percorso per bisogno, elenco dei servizi con filtri, ricerca, mappa, schede di servizio
 * e organizzazione (vault "61", "62", "65"). Solo contenuti pubblicati e recapiti pubblici.
 */
final class CatalogController extends Controller
{
    private const PER_PAGE = 20;
    private const MAP_LIST_LIMIT = 200;

    public function need(Request $request): Response
    {
        $need = $this->catalog()->need((string) $request->attribute('code'), $this->view()->locale())
            ?? throw new HttpException(404);

        return $this->listing($request, ['need' => $need['code']], 'public.need', [
            'pageTitle' => $need['label']['text'],
            'need' => $need,
        ]);
    }

    public function services(Request $request): Response
    {
        return $this->listing($request, [], 'public.services', ['pageTitle' => $this->t('catalog.services.title')]);
    }

    public function search(Request $request): Response
    {
        return $this->listing($request, [], 'public.search', ['pageTitle' => $this->t('nav.search'), 'isSearch' => true]);
    }

    /** Mappa con elenco equivalente sempre visibile (la mappa non è mai l'unico accesso, RF-09). */
    public function map(Request $request): Response
    {
        $locale = $this->view()->locale();
        $catalog = $this->catalog();
        $filters = CatalogFilters::fromRequest($request);
        $ids = $catalog->searchServiceIds($filters, $locale);

        return $this->render('public/map', [
            'pageTitle' => $this->t('nav.map'),
            'filters' => $filters,
            'query' => CatalogFilters::toQuery($filters),
            'total' => count($ids),
            'services' => $catalog->serviceSummaries(array_slice($ids, 0, self::MAP_LIST_LIMIT), $locale),
            'map' => [
                'tiles' => (string) Env::get('MAP_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
                'attribution' => (string) Env::get('MAP_TILE_ATTRIBUTION', '© OpenStreetMap contributors'),
                'center' => (string) Env::get('MAP_DEFAULT_CENTER', '45.0526,9.6930'),
                'zoom' => (int) Env::get('MAP_DEFAULT_ZOOM', '10'),
            ],
            ...$this->filterOptions($locale),
        ]);
    }

    public function service(Request $request): Response
    {
        $service = $this->catalog()->service((int) $request->attribute('id'), $this->view()->locale()) ?? throw new HttpException(404);

        return $this->render('public/service', [
            'pageTitle' => $service['texts']['name']['text'],
            'service' => $service,
        ]);
    }

    public function organization(Request $request): Response
    {
        $locale = $this->view()->locale();
        $catalog = $this->catalog();
        $organization = $catalog->organization((int) $request->attribute('id'), $locale) ?? throw new HttpException(404);

        return $this->render('public/organization', [
            'pageTitle' => (string) $organization['name'],
            'organization' => $organization,
            'profile' => $this->container->get(CommunityRepository::class)->profileOf($organization['id'], $locale),
            'services' => $catalog->serviceSummaries($organization['service_ids'], $locale),
        ]);
    }

    /**
     * @param array<string, string> $fixed filtri imposti dalla pagina (es. bisogno)
     * @param array<string, mixed> $data
     */
    private function listing(Request $request, array $fixed, string $routeName, array $data): Response
    {
        $locale = $this->view()->locale();
        $catalog = $this->catalog();
        $filters = [...CatalogFilters::fromRequest($request), ...$fixed];

        $ids = $catalog->searchServiceIds($filters, $locale);
        $total = count($ids);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, $request->int('page', 1)), $pages);
        $query = CatalogFilters::toQuery($filters);
        unset($query['bisogno']);

        return $this->render('public/services', [
            ...$data,
            'services' => $catalog->serviceSummaries(array_slice($ids, ($page - 1) * self::PER_PAGE, self::PER_PAGE), $locale),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'filters' => $filters,
            'query' => $query,
            'mapQuery' => CatalogFilters::toQuery($filters),
            'routeName' => $routeName,
            'routeParams' => isset($fixed['need']) ? ['code' => $fixed['need']] : [],
            ...$this->filterOptions($locale),
        ]);
    }

    /** @return array<string, mixed> */
    private function filterOptions(string $locale): array
    {
        $catalog = $this->catalog();
        $this->view()->share('municipality_centroids', $catalog->municipalities());

        return [
            'categories' => array_values(array_filter($catalog->categories($locale), static fn (array $c): bool => $c['parent_id'] === null)),
            'municipalities' => $catalog->municipalitiesWithServices(),
            'languages' => $catalog->spokenLanguages(),
            'orgTypes' => $catalog->organizationTypesWithServices($locale),
            'needs' => $catalog->needs($locale, true),
        ];
    }

    private function catalog(): CatalogRepository
    {
        return $this->container->get(CatalogRepository::class);
    }
}
