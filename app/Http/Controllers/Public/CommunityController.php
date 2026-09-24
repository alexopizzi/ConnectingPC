<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Communities\CommunityRepository;
use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;

/**
 * Directory pubblica "Associazioni e comunità" (vault "64"): filtri per nome, lingua, comunità, paese,
 * comune o distretto, tipo, realtà create da cittadini stranieri.
 */
final class CommunityController extends Controller
{
    private const PER_PAGE = 20;

    public function index(Request $request): Response
    {
        $locale = $this->view()->locale();
        $repository = $this->container->get(CommunityRepository::class);
        $filters = CommunityRepository::filtersFromRequest($request);

        $ids = $repository->searchOrganizationIds($filters, $locale);
        $total = count($ids);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min(max(1, $request->int('page', 1)), $pages);

        return $this->render('public/communities', [
            'pageTitle' => $this->t('nav.communities'),
            'organizations' => $repository->summaries(array_slice($ids, ($page - 1) * self::PER_PAGE, self::PER_PAGE), $locale),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'filters' => $filters,
            'query' => CommunityRepository::filtersToQuery($filters),
            'communities' => $repository->communities($locale),
            'countries' => $repository->countries(),
            'languages' => $repository->languages(),
            'types' => $repository->types($locale),
            'municipalities' => $repository->municipalities(),
        ]);
    }
}
