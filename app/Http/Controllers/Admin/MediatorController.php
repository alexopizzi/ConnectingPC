<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Management\MediatorEditor;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use DomainException;

/** Mediatori in admin (permesso mediators.manage): elenco, scheda con consenso e visibilità, testi, recapiti. */
final class MediatorController extends ManagementController
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => mb_substr($request->string('q'), 0, 100),
            'visibility' => in_array($request->string('visibilita'), MediatorEditor::VISIBILITY, true) ? $request->string('visibilita') : '',
            'organization' => $request->int('organizzazione'),
        ];

        return $this->page('admin/mediators/index', [
            'pageTitle' => $this->t('admin.mediators.title'),
            'mediators' => $this->management()->mediators($filters),
            'filters' => $filters,
            'organizations' => $this->management()->organizationOptions(),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->page('admin/mediators/edit', ['pageTitle' => $this->t('admin.mediators.create'), 'mediator' => null, ...$this->options(), 'textLocale' => 'it']);
    }

    public function store(Request $request): Response
    {
        try {
            $id = $this->editor()->save($this->user($request), null, $this->data($request));
        } catch (DomainException $e) {
            return $this->backWithErrors($request, $this->view()->route('admin.mediators.create'), ['form' => [$this->t($e->getMessage())]]);
        }
        $this->flash('success', $this->t('manage.saved'));

        return $this->redirectTo('admin.mediators.show', ['id' => $id]);
    }

    public function show(Request $request): Response
    {
        $mediator = $this->management()->mediator((int) $request->attribute('id')) ?? throw new HttpException(404);

        return $this->page('admin/mediators/edit', [
            'pageTitle' => $mediator['first_name'] . ' ' . $mediator['last_name'],
            'mediator' => $mediator,
            ...$this->options(),
            'textLocale' => $this->textLocale($request, 'it'),
        ]);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->attribute('id');

        return $this->attempt(fn () => $this->editor()->save($this->user($request), $id, $this->data($request)), 'manage.saved', 'admin.mediators.show', ['id' => $id]);
    }

    public function saveTexts(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $locale = $request->string('locale');

        return $this->attempt(
            fn () => $this->editor()->saveTexts($this->user($request), $id, $locale, $this->texts($request, MediatorEditor::TEXT_FIELDS), $request->string('approve') === '1'),
            'manage.saved', 'admin.mediators.show', ['id' => $id, 'lingua' => $locale],
        );
    }

    public function saveContacts(Request $request): Response
    {
        $id = (int) $request->attribute('id');

        return $this->attempt(fn () => $this->editor()->saveContacts($this->user($request), $id, $this->rows($request, 'contacts')), 'manage.saved', 'admin.mediators.show', ['id' => $id]);
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
    private function options(): array
    {
        return [
            'organizations' => $this->management()->organizationOptions(),
            'languages' => $this->management()->languages(),
            'domains' => $this->management()->mediationDomains(),
            'wideTerritories' => $this->management()->wideTerritories(),
            'municipalities' => $this->management()->districtTree(),
            'locales' => $this->contentLocales(),
        ];
    }

    private function editor(): MediatorEditor
    {
        return $this->container->get(MediatorEditor::class);
    }
}
