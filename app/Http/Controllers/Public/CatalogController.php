<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Catalog\CatalogRepository;
use App\Http\Controller;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

/**
 * Catalogo pubblico: percorso per bisogno, elenco dei servizi con filtri, ricerca, schede di servizio
 * e organizzazione (vault "62", "65"). Solo contenuti pubblicati e recapiti pubblici.
 */
final class CatalogController extends Controller
{
    private const PER_PAGE = 20;

    public function need(Request $request): Response
    {
        $catalog = $this->catalog();
        $need = $catalog->need((string) $request->attribute('code'), $this->view()->locale())
            ?? throw new HttpException(404);

        return $this->listing($request, ['need' => $need['code']], 'public.need', [
            'pageTitle' => $need['label']['text'],
            'heading' => $need['label'],
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

    public function service(Request $request): Response
    {
        $locale = $this->view()->locale();
        $service = $this->catalog()->service((int) $request->attribute('id'), $locale) ?? throw new HttpException(404);

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
        $filters = [...$this->filters($request), ...$fixed];

        $ids = $catalog->searchServiceIds($filters, $locale);
        $total = count($ids);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, $request->int('page', 1)), $pages);
        $services = $catalog->serviceSummaries(array_slice($ids, ($page - 1) * self::PER_PAGE, self::PER_PAGE), $locale);

        // Parametri da conservare nei link di paginazione e nel modulo dei filtri
        $query = array_filter([
            'q' => $filters['q'],
            'categoria' => $filters['category'] ?? '',
            'comune' => $filters['territory'] ? (string) $filters['territory'] : '',
            'lingua' => $filters['language'] ?? '',
            'mediazione' => $filters['mediation'] ? '1' : '',
            'gratuito' => $filters['free'] ? '1' : '',
        ], static fn (string $v): bool => $v !== '');

        return $this->render('public/services', [
            ...$data,
            'services' => $services,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'filters' => $filters,
            'query' => $query,
            'routeName' => $routeName,
            'routeParams' => isset($fixed['need']) ? ['code' => $fixed['need']] : [],
            'categories' => array_values(array_filter($catalog->categories($locale), static fn (array $c): bool => $c['parent_id'] === null)),
            'municipalities' => $catalog->municipalitiesWithServices(),
            'languages' => $catalog->spokenLanguages(),
            'needs' => $catalog->needs($locale, true),
        ]);
    }

    /** @return array{q: string, category: ?string, territory: ?int, language: ?string, mediation: bool, free: bool} */
    private function filters(Request $request): array
    {
        $category = $request->string('categoria');
        $language = $request->string('lingua');
        $territory = $request->int('comune');

        return [
            'q' => mb_substr($request->string('q'), 0, 200),
            'category' => preg_match('/^[a-z_]{2,60}$/', $category) ? $category : null,
            'territory' => $territory > 0 ? $territory : null,
            'language' => preg_match('/^[a-z]{2,3}$/', $language) ? $language : null,
            'mediation' => $request->string('mediazione') === '1',
            'free' => $request->string('gratuito') === '1',
        ];
    }

    private function catalog(): CatalogRepository
    {
        return $this->container->get(CatalogRepository::class);
    }
}
