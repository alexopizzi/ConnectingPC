<?php

declare(strict_types=1);

namespace App\Http\Controllers\Manage;

use App\Core\Validator;
use App\Domain\Management\OrganizationEditor;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

/**
 * Scheda di gestione di un'organizzazione (profilo, testi, collegamenti, recapiti, elenchi di sedi,
 * servizi e mediatori). I permessi di scrittura li verifica OrganizationEditor.
 */
abstract class OrganizationController extends ManagementController
{
    public function show(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $this->authorizeOrganization($request, $id);
        $organization = $this->management()->organization($id) ?? throw new HttpException(404);

        return $this->page('manage/organization', [
            'pageTitle' => (string) $organization['name'],
            'organization' => $organization,
            'textLocale' => $this->textLocale($request, (string) $organization['source_locale']),
            'locales' => $this->contentLocales(),
            'types' => $this->management()->organizationTypes($this->view()->locale()),
            'languages' => $this->management()->languages(),
            'communities' => $this->management()->communities($this->view()->locale()),
            'can' => $this->capabilities($request, $id),
        ]);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $errors = Validator::validate($request->body, ['name' => ['required', 'max:255'], 'short_name' => ['max:80'], 'website' => ['max:500']]);
        if ($errors !== []) {
            return $this->backWithErrors($request, $this->view()->route($this->route('organizations.show'), ['id' => $id]), $this->translateErrors($errors));
        }

        return $this->attempt(fn () => $this->editor()->updateProfile($this->user($request), $id, [
            'name' => $request->string('name'),
            'short_name' => $request->string('short_name'),
            'website' => $request->string('website'),
            'organization_type_id' => $request->int('organization_type_id'),
            'is_community_based' => $request->string('is_community_based') === '1',
            'source_locale' => $request->string('source_locale'),
        ]), 'manage.saved', 'organizations.show', ['id' => $id]);
    }

    public function saveTexts(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $locale = $request->string('locale');

        return $this->attempt(
            fn () => $this->editor()->saveTexts($this->user($request), $id, $locale, $this->texts($request, OrganizationEditor::TEXT_FIELDS), $request->string('approve') === '1'),
            'manage.saved', 'organizations.show', ['id' => $id, 'lingua' => $locale],
        );
    }

    public function saveLinks(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $countries = preg_split('/[\s,;]+/', strtoupper($request->string('countries')), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return $this->attempt(
            fn () => $this->editor()->saveLinks($this->user($request), $id, $this->values($request, 'languages'), $this->ids($request, 'communities'), $countries),
            'manage.saved', 'organizations.show', ['id' => $id],
        );
    }

    public function saveContacts(Request $request): Response
    {
        $id = (int) $request->attribute('id');

        return $this->attempt(fn () => $this->editor()->saveContacts($this->user($request), $id, $this->rows($request, 'contacts')), 'manage.saved', 'organizations.show', ['id' => $id]);
    }

    protected function editor(): OrganizationEditor
    {
        return $this->container->get(OrganizationEditor::class);
    }
}
