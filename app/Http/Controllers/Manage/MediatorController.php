<?php

declare(strict_types=1);

namespace App\Http\Controllers\Manage;

use App\Domain\Management\MediatorEditor;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use DomainException;

/** Mediatori: scheda con consenso e visibilità, testi, recapiti (regole in MediatorEditor). */
abstract class MediatorController extends ManagementController
{
    /**
     * Organizzazioni selezionabili come appartenenza del mediatore.
     *
     * @return list<array{id: int, name: string}>
     */
    abstract protected function organizationChoices(Request $request): array;

    public function show(Request $request): Response
    {
        $mediator = $this->management()->mediator((int) $request->attribute('id')) ?? throw new HttpException(404);
        if ($this->area() === 'portal') {
            // Nell'area riservata si vedono solo i mediatori delle proprie organizzazioni
            $organizationId = (int) $mediator['organization_id'];
            if ($organizationId === 0 || !in_array($organizationId, array_column($this->organizationChoices($request), 'id'), true)) {
                throw new HttpException(404);
            }
        }

        return $this->page('manage/mediator', [
            'pageTitle' => $mediator['first_name'] . ' ' . $mediator['last_name'],
            'mediator' => $mediator,
            ...$this->options($request),
            'textLocale' => $this->textLocale($request, 'it'),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->page('manage/mediator', ['pageTitle' => $this->t('manage.mediators.create'), 'mediator' => null, ...$this->options($request), 'textLocale' => 'it']);
    }

    public function store(Request $request): Response
    {
        try {
            $id = $this->editor()->save($this->user($request), null, $this->data($request));
        } catch (DomainException $e) {
            return $this->backWithDomainError($request, 'mediators.create', [], $e);
        }
        $this->flash('success', $this->t('manage.saved'));

        return $this->redirectTo($this->route('mediators.show'), ['id' => $id]);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->attribute('id');

        return $this->attempt(fn () => $this->editor()->save($this->user($request), $id, $this->data($request)), 'manage.saved', 'mediators.show', ['id' => $id]);
    }

    public function revokeConsent(Request $request): Response
    {
        $id = (int) $request->attribute('id');

        return $this->attempt(fn () => $this->editor()->revokeConsent($this->user($request), $id), 'manage.mediators.consent_revoked', 'mediators.show', ['id' => $id]);
    }

    public function saveTexts(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $locale = $request->string('locale');

        return $this->attempt(
            fn () => $this->editor()->saveTexts($this->user($request), $id, $locale, $this->texts($request, MediatorEditor::TEXT_FIELDS), $request->string('approve') === '1'),
            'manage.saved', 'mediators.show', ['id' => $id, 'lingua' => $locale],
        );
    }

    public function saveContacts(Request $request): Response
    {
        $id = (int) $request->attribute('id');

        return $this->attempt(fn () => $this->editor()->saveContacts($this->user($request), $id, $this->rows($request, 'contacts')), 'manage.saved', 'mediators.show', ['id' => $id]);
    }

    /** @return array<string, mixed> */
    private function data(Request $request): array
    {
        $data = [];
        foreach (['first_name', 'last_name', 'public_display_name', 'organization_id', 'availability', 'profile_visibility', 'consent_reference',
            'verification_status', 'qualifications_admin', 'admin_notes', 'next_review_at', 'publication_status'] as $field) {
            $data[$field] = $request->string($field);
        }

        return [
            ...$data,
            'consent_given' => $request->string('consent_given') === '1',
            'mediation_types' => $this->values($request, 'mediation_types'),
            'languages' => $this->rows($request, 'languages'),
            'domains' => $this->ids($request, 'domains'),
            'territories' => $this->ids($request, 'territories'),
        ];
    }

    /** @return array<string, mixed> */
    private function options(Request $request): array
    {
        return [
            'organizations' => $this->organizationChoices($request),
            'languages' => $this->management()->languages(),
            'domains' => $this->management()->mediationDomains($this->view()->locale()),
            'wideTerritories' => $this->management()->wideTerritories(),
            'municipalities' => $this->management()->districtTree(),
            'locales' => $this->contentLocales(),
            'presetOrganization' => $request->int('organizzazione'),
        ];
    }

    private function editor(): MediatorEditor
    {
        return $this->container->get(MediatorEditor::class);
    }
}
