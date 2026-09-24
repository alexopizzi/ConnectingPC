<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Management\SiteEditor;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use DomainException;

/** Sedi in admin: creazione dalla scheda dell'organizzazione, dati, orari, recapiti, testi. */
final class SiteController extends ManagementController
{
    private const FIELDS = [
        'name', 'address_line', 'postal_code', 'territory_id', 'lat', 'lng', 'is_public_place',
        'step_free_access', 'accessible_toilet', 'publication_status',
    ];

    public function create(Request $request): Response
    {
        $organization = $this->management()->organization((int) $request->attribute('id')) ?? throw new HttpException(404);

        return $this->page('admin/sites/edit', [
            'pageTitle' => $this->t('admin.sites.create'),
            'organization' => $organization,
            'site' => null,
            'municipalities' => $this->management()->districtTree(),
            'locales' => $this->contentLocales(),
            'textLocale' => (string) $organization['source_locale'],
        ]);
    }

    public function store(Request $request): Response
    {
        $organizationId = (int) $request->attribute('id');
        try {
            $result = $this->container->get(SiteEditor::class)->save($this->user($request), $organizationId, null, $this->fields($request));
        } catch (DomainException $e) {
            return $this->backWithErrors($request, $this->view()->route('admin.sites.create', ['id' => $organizationId]), ['form' => [$this->t($e->getMessage())]]);
        }
        $this->flash('success', $this->t('manage.saved'));
        if ($result['warning'] !== null) {
            $this->flash('warning', $this->t($result['warning']));
        }

        return $this->redirectTo('admin.sites.show', ['id' => $result['id']]);
    }

    public function show(Request $request): Response
    {
        $site = $this->management()->site((int) $request->attribute('id')) ?? throw new HttpException(404);
        $organization = $this->management()->organization((int) $site['organization_id']) ?? throw new HttpException(404);

        return $this->page('admin/sites/edit', [
            'pageTitle' => $site['name'] ?: $site['address_line'],
            'organization' => $organization,
            'site' => $site,
            'municipalities' => $this->management()->districtTree(),
            'locales' => $this->contentLocales(),
            'textLocale' => $this->textLocale($request, (string) $site['source_locale']),
        ]);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $site = $this->management()->site($id) ?? throw new HttpException(404);

        return $this->attempt(
            fn () => $this->editor()->save($this->user($request), (int) $site['organization_id'], $id, $this->fields($request))['warning'],
            'manage.saved', 'admin.sites.show', ['id' => $id],
        );
    }

    public function saveHours(Request $request): Response
    {
        $id = (int) $request->attribute('id');

        return $this->attempt(fn () => $this->editor()->saveHours($this->user($request), $id, $this->rows($request, 'hours')), 'manage.saved', 'admin.sites.show', ['id' => $id]);
    }

    public function saveContacts(Request $request): Response
    {
        $id = (int) $request->attribute('id');

        return $this->attempt(fn () => $this->editor()->saveContacts($this->user($request), $id, $this->rows($request, 'contacts')), 'manage.saved', 'admin.sites.show', ['id' => $id]);
    }

    public function saveTexts(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $locale = $request->string('locale');

        return $this->attempt(
            fn () => $this->editor()->saveTexts($this->user($request), $id, $locale, $this->texts($request, SiteEditor::TEXT_FIELDS), $request->string('approve') === '1'),
            'manage.saved', 'admin.sites.show', ['id' => $id, 'lingua' => $locale],
        );
    }

    /** @return array<string, string> */
    private function fields(Request $request): array
    {
        $fields = [];
        foreach (self::FIELDS as $field) {
            $fields[$field] = $request->string($field);
        }

        return $fields;
    }

    private function editor(): SiteEditor
    {
        return $this->container->get(SiteEditor::class);
    }
}
