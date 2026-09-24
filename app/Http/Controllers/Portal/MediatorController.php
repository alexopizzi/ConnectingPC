<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Authorization\Gate;
use App\Domain\Mediators\MediatorRepository;
use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;

/**
 * Mediatori per gli operatori autenticati (vault "63"): profili "pubblici" e "solo operatori",
 * con nome completo e recapiti per operatori. Richiede il permesso restricted.view (verificato dal Gate).
 */
final class MediatorController extends Controller
{
    public function index(Request $request): Response
    {
        $this->container->get(Gate::class)->authorize($this->user($request), 'restricted.view');

        $locale = $this->view()->locale();
        $repository = $this->container->get(MediatorRepository::class);
        $filters = MediatorRepository::filtersFromRequest($request);

        return $this->render('public/mediators', [
            'pageTitle' => $this->t('mediators.operators.title'),
            'profiles' => $repository->operatorProfiles($filters, $locale),
            'groups' => [],
            'filters' => $filters,
            'query' => MediatorRepository::filtersToQuery($filters),
            'domains' => $repository->domains($locale),
            'languages' => $repository->languages(),
            'municipalities' => $repository->municipalities(),
            'audience' => 'operators',
            'formRoute' => 'portal.mediators',
        ]);
    }
}
