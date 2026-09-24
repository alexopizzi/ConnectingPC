<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Auth\Auth;
use App\Authorization\Gate;
use App\Domain\Management\ManagementRepository;
use App\Http\Controller;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

/**
 * Area riservata delle organizzazioni: cruscotto (le funzioni di gestione arrivano con v0.9.0).
 * Mostra solo le organizzazioni in cui l'utente ha accesso (filtro sempre lato server).
 */
final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $this->user($request);
        $gate = $this->container->get(Gate::class);
        $organizationIds = $gate->organizationsWith($user, 'portal.access');
        if ($organizationIds === [] && !$gate->allows($user, 'admin.access')) {
            throw new HttpException(403);
        }

        $organizations = [];
        if ($organizationIds !== []) {
            $placeholders = implode(',', array_fill(0, count($organizationIds), '?'));
            $organizations = $this->db()->fetchAll(
                "SELECT o.id, o.name, o.access_status, o.publication_policy, o.portal_edit_enabled,
                        COALESCE(tt.name, ti.name) AS type_name
                   FROM organizations o
                   JOIN organization_types t ON t.id = o.organization_type_id
                   LEFT JOIN organization_type_translations tt ON tt.organization_type_id = t.id AND tt.locale = ?
                   LEFT JOIN organization_type_translations ti ON ti.organization_type_id = t.id AND ti.locale = 'it'
                  WHERE o.id IN ($placeholders)
                  ORDER BY o.name",
                [$this->view()->locale(), ...$organizationIds],
            );
        }

        $auth = $this->container->get(Auth::class);
        if ($auth->activeOrganizationId() === null && count($organizationIds) === 1) {
            $auth->setActiveOrganization($organizationIds[0]);
        }

        return $this->render('portal/dashboard', [
            'pageTitle' => $this->t('portal.dashboard.title'),
            'organizations' => $organizations,
            'work' => $this->container->get(ManagementRepository::class)->pendingWork($organizationIds),
            'gate' => $gate,
            'user' => $user,
        ]);
    }
}
