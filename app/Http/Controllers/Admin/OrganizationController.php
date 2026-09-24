<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Validator;
use App\Domain\Management\EditorialGuard;
use App\Domain\Management\OrganizationEditor;
use App\Http\Controllers\Manage\OrganizationController as ManageOrganizationController;
use App\Http\Request;
use App\Http\Response;
use DomainException;

/** Organizzazioni in admin: elenco, creazione, assi di stato; la scheda è condivisa con l'area riservata. */
final class OrganizationController extends ManageOrganizationController
{
    protected function area(): string
    {
        return 'admin';
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => mb_substr($request->string('q'), 0, 100),
            'access' => in_array($request->string('accesso'), OrganizationEditor::STATUS_AXES['access_status'], true) ? $request->string('accesso') : '',
            'publication' => in_array($request->string('pubblicazione'), EditorialGuard::PUBLICATION_STATUSES, true) ? $request->string('pubblicazione') : '',
            'type' => $request->int('tipo'),
        ];
        $page = max(1, $request->int('page', 1));
        $result = $this->management()->organizations($filters, self::PER_PAGE, ($page - 1) * self::PER_PAGE);

        return $this->page('admin/organizations/index', [
            'pageTitle' => $this->t('admin.organizations.title'),
            'organizations' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'pages' => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'filters' => $filters,
            'types' => $this->management()->organizationTypes(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->page('admin/organizations/create', [
            'pageTitle' => $this->t('admin.organizations.create'),
            'types' => $this->management()->organizationTypes(),
            'locales' => $this->contentLocales(),
        ]);
    }

    public function store(Request $request): Response
    {
        $errors = Validator::validate($request->body, ['name' => ['required', 'max:255'], 'organization_type_id' => ['required', 'integer']]);
        if ($errors !== []) {
            return $this->backWithErrors($request, $this->view()->route('admin.organizations.create'), $this->translateErrors($errors));
        }
        try {
            $id = $this->editor()->create($this->user($request), [
                'name' => $request->string('name'),
                'organization_type_id' => $request->int('organization_type_id'),
                'is_community_based' => $request->string('is_community_based') === '1',
                'source_locale' => $request->string('source_locale', 'it'),
            ]);
        } catch (DomainException $e) {
            return $this->backWithDomainError($request, 'organizations.create', [], $e);
        }
        $this->flash('success', $this->t('manage.saved'));

        return $this->redirectTo('admin.organizations.show', ['id' => $id]);
    }

    public function updateStatus(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $data = array_intersect_key($request->body, array_flip([
            'census_status', 'listing_status', 'verification_status', 'access_status', 'publication_policy',
            'publication_status', 'next_review_at', 'status_note',
        ]));
        $data['portal_edit_enabled'] = $request->string('portal_edit_enabled') === '1';

        return $this->attempt(fn () => $this->editor()->updateStatus($this->user($request), $id, $data), 'manage.saved', 'organizations.show', ['id' => $id]);
    }
}
