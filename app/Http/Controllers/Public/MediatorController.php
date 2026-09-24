<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Mediators\MediatorRepository;
use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;

/**
 * Mediatori per il pubblico (vault "63", D-033): profili individuali solo con consenso,
 * gli altri solo in forma aggregata tramite l'organizzazione. Nessun dato riservato.
 */
final class MediatorController extends Controller
{
    public function index(Request $request): Response
    {
        $locale = $this->view()->locale();
        $repository = $this->container->get(MediatorRepository::class);
        $filters = MediatorRepository::filtersFromRequest($request);

        return $this->render('public/mediators', [
            'pageTitle' => $this->t('nav.mediators'),
            'profiles' => $repository->publicProfiles($filters, $locale),
            'groups' => $repository->aggregatedByOrganization($filters, $locale),
            'filters' => $filters,
            'query' => MediatorRepository::filtersToQuery($filters),
            'domains' => $repository->domains($locale),
            'languages' => $repository->languages(),
            'municipalities' => $repository->municipalities(),
            'audience' => 'public',
            'formRoute' => 'public.mediators',
        ]);
    }
}
