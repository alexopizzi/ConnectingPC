<?php

declare(strict_types=1);

namespace App\Http\Controllers\Manage;

use App\Domain\Geo\Geocoder;
use App\Domain\Management\EditorialGuard;
use App\Domain\Management\SiteEditor;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use DomainException;

/** Sedi: creazione dalla scheda dell'organizzazione, dati, orari, recapiti, testi. */
abstract class SiteController extends ManagementController
{
    private const FIELDS = [
        'name', 'address_line', 'postal_code', 'territory_id', 'lat', 'lng', 'is_public_place',
        'step_free_access', 'accessible_toilet', 'publication_status',
    ];

    public function create(Request $request): Response
    {
        $organizationId = (int) $request->attribute('id');
        $this->authorizeOrganization($request, $organizationId);
        $organization = $this->management()->organization($organizationId) ?? throw new HttpException(404);

        return $this->page('manage/site', [
            'pageTitle' => $this->t('manage.sites.create'),
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
            $result = $this->editor()->save($this->user($request), $organizationId, null, $this->fields($request));
        } catch (DomainException $e) {
            return $this->backWithDomainError($request, 'sites.create', ['id' => $organizationId], $e);
        }
        $this->flash('success', $this->t('manage.saved'));
        if ($result['warning'] !== null) {
            $this->flash('warning', $this->t($result['warning']));
        }

        return $this->redirectTo($this->route('sites.show'), ['id' => $result['id']]);
    }

    public function show(Request $request): Response
    {
        $site = $this->management()->site((int) $request->attribute('id')) ?? throw new HttpException(404);
        $this->authorizeOrganization($request, (int) $site['organization_id']);
        $organization = $this->management()->organization((int) $site['organization_id']) ?? throw new HttpException(404);

        return $this->page('manage/site', [
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
            'manage.saved', 'sites.show', ['id' => $id],
        );
    }

    public function saveHours(Request $request): Response
    {
        $id = (int) $request->attribute('id');

        return $this->attempt(fn () => $this->editor()->saveHours($this->user($request), $id, $this->rows($request, 'hours')), 'manage.saved', 'sites.show', ['id' => $id]);
    }

    public function saveContacts(Request $request): Response
    {
        $id = (int) $request->attribute('id');

        return $this->attempt(fn () => $this->editor()->saveContacts($this->user($request), $id, $this->rows($request, 'contacts')), 'manage.saved', 'sites.show', ['id' => $id]);
    }

    public function saveTexts(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $locale = $request->string('locale');

        return $this->attempt(
            fn () => $this->editor()->saveTexts($this->user($request), $id, $locale, $this->texts($request, SiteEditor::TEXT_FIELDS), $request->string('approve') === '1'),
            'manage.saved', 'sites.show', ['id' => $id, 'lingua' => $locale],
        );
    }

    /**
     * Proposte di coordinate per un indirizzo (RF-40): JSON per il modulo della sede. Solo per chi può
     * modificare le sedi dell'organizzazione; la posizione va poi confermata a mano sulla mappa.
     */
    public function geocode(Request $request): Response
    {
        $organizationId = (int) $request->attribute('id');
        if (!$this->container->get(EditorialGuard::class)->allows($this->user($request), 'org.sites.edit', $organizationId)) {
            return Response::json(['error' => ['code' => 'forbidden', 'message' => $this->t('manage.error.forbidden')]], 403);
        }
        $town = (string) $this->db()->fetchValue("SELECT name FROM territories WHERE id = ? AND type = 'municipality'", [$request->int('territory_id')]);
        try {
            $results = $this->container->get(Geocoder::class)->search(
                mb_substr($request->string('address_line'), 0, 255),
                mb_substr($request->string('postal_code'), 0, 10),
                $town,
            );
        } catch (\RuntimeException) {
            return Response::json(['error' => ['code' => 'unavailable', 'message' => $this->t('manage.geocode.unavailable')]], 503);
        }

        return Response::json(['results' => $results]);
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
