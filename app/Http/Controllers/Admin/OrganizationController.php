<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Core\Validator;
use App\Domain\Management\OrganizationEditor;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use DomainException;

/** Organizzazioni in admin: elenco, creazione, scheda di gestione (permessi verificati da OrganizationEditor). */
final class OrganizationController extends ManagementController
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => mb_substr($request->string('q'), 0, 100),
            'access' => in_array($request->string('accesso'), OrganizationEditor::STATUS_AXES['access_status'], true) ? $request->string('accesso') : '',
            'publication' => in_array($request->string('pubblicazione'), ['draft', 'in_review', 'published', 'archived'], true) ? $request->string('pubblicazione') : '',
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
            $id = $this->container->get(OrganizationEditor::class)->create($this->user($request), [
                'name' => $request->string('name'),
                'organization_type_id' => $request->int('organization_type_id'),
                'is_community_based' => $request->string('is_community_based') === '1',
                'source_locale' => $request->string('source_locale', 'it'),
            ]);
        } catch (DomainException $e) {
            return $this->backWithErrors($request, $this->view()->route('admin.organizations.create'), ['form' => [$this->t($e->getMessage())]]);
        }
        $this->flash('success', $this->t('manage.saved'));

        return $this->redirectTo('admin.organizations.show', ['id' => $id]);
    }

    public function show(Request $request): Response
    {
        $organization = $this->management()->organization((int) $request->attribute('id')) ?? throw new HttpException(404);
        $locale = $this->textLocale($request, (string) $organization['source_locale']);

        return $this->page('admin/organizations/show', [
            'pageTitle' => (string) $organization['name'],
            'organization' => $organization,
            'textLocale' => $locale,
            'locales' => $this->contentLocales(),
            'types' => $this->management()->organizationTypes(),
            'languages' => $this->management()->languages(),
            'communities' => $this->management()->communities(),
        ]);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $errors = Validator::validate($request->body, ['name' => ['required', 'max:255'], 'short_name' => ['max:80'], 'website' => ['max:500']]);
        if ($errors !== []) {
            return $this->backWithErrors($request, $this->view()->route('admin.organizations.show', ['id' => $id]), $this->translateErrors($errors));
        }

        return $this->attempt(fn () => $this->editor()->updateProfile($this->user($request), $id, [
            'name' => $request->string('name'),
            'short_name' => $request->string('short_name'),
            'website' => $request->string('website'),
            'organization_type_id' => $request->int('organization_type_id'),
            'is_community_based' => $request->string('is_community_based') === '1',
            'source_locale' => $request->string('source_locale'),
        ]), 'manage.saved', 'admin.organizations.show', ['id' => $id]);
    }

    public function updateStatus(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $data = array_intersect_key($request->body, array_flip([
            'census_status', 'listing_status', 'verification_status', 'access_status', 'publication_policy',
            'publication_status', 'next_review_at', 'status_note',
        ]));
        $data['portal_edit_enabled'] = $request->string('portal_edit_enabled') === '1';

        return $this->attempt(fn () => $this->editor()->updateStatus($this->user($request), $id, $data), 'manage.saved', 'admin.organizations.show', ['id' => $id]);
    }

    public function saveTexts(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $locale = $request->string('locale');

        return $this->attempt(
            fn () => $this->editor()->saveTexts($this->user($request), $id, $locale, $this->texts($request, OrganizationEditor::TEXT_FIELDS), $request->string('approve') === '1'),
            'manage.saved', 'admin.organizations.show', ['id' => $id, 'lingua' => $locale],
        );
    }

    public function saveLinks(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $countries = preg_split('/[\s,;]+/', strtoupper($request->string('countries')), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return $this->attempt(
            fn () => $this->editor()->saveLinks($this->user($request), $id, $this->values($request, 'languages'), $this->ids($request, 'communities'), $countries),
            'manage.saved', 'admin.organizations.show', ['id' => $id],
        );
    }

    public function saveContacts(Request $request): Response
    {
        $id = (int) $request->attribute('id');

        return $this->attempt(fn () => $this->editor()->saveContacts($this->user($request), $id, $this->rows($request, 'contacts')), 'manage.saved', 'admin.organizations.show', ['id' => $id]);
    }

    private function editor(): OrganizationEditor
    {
        return $this->container->get(OrganizationEditor::class);
    }
}
