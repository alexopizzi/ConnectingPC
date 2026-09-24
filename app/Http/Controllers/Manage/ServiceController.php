<?php

declare(strict_types=1);

namespace App\Http\Controllers\Manage;

use App\Domain\Management\ServiceEditor;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use DomainException;

/** Servizi: creazione dalla scheda dell'organizzazione, dati e testi tradotti. */
abstract class ServiceController extends ManagementController
{
    public function create(Request $request): Response
    {
        $organizationId = (int) $request->attribute('id');
        $this->authorizeOrganization($request, $organizationId);
        $organization = $this->management()->organization($organizationId) ?? throw new HttpException(404);

        return $this->page('manage/service', [
            'pageTitle' => $this->t('manage.services.create'),
            'organization' => $organization,
            'service' => null,
            'organizationSites' => $this->management()->organizationSites($organizationId),
            ...$this->options(),
            'textLocale' => (string) $organization['source_locale'],
        ]);
    }

    public function store(Request $request): Response
    {
        $organizationId = (int) $request->attribute('id');
        try {
            $id = $this->editor()->create($this->user($request), $organizationId, $request->string('name'), $this->data($request));
        } catch (DomainException $e) {
            return $this->backWithDomainError($request, 'services.create', ['id' => $organizationId], $e);
        }
        $this->flash('success', $this->t('manage.saved'));

        return $this->redirectTo($this->route('services.show'), ['id' => $id]);
    }

    public function show(Request $request): Response
    {
        $service = $this->management()->service((int) $request->attribute('id')) ?? throw new HttpException(404);
        $this->authorizeOrganization($request, (int) $service['organization_id']);
        $organization = $this->management()->organization((int) $service['organization_id']) ?? throw new HttpException(404);

        return $this->page('manage/service', [
            'pageTitle' => (string) ($service['translations'][$service['source_locale']]['name'] ?? '#' . $service['id']),
            'organization' => $organization,
            'service' => $service,
            'organizationSites' => $service['organization_sites'],
            ...$this->options(),
            'textLocale' => $this->textLocale($request, (string) $service['source_locale']),
        ]);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->attribute('id');

        return $this->attempt(fn () => $this->editor()->update($this->user($request), $id, $this->data($request)), 'manage.saved', 'services.show', ['id' => $id]);
    }

    public function saveTexts(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $locale = $request->string('locale');

        return $this->attempt(
            fn () => $this->editor()->saveTexts($this->user($request), $id, $locale, $this->texts($request, ServiceEditor::TEXT_FIELDS), $request->string('approve') === '1'),
            'manage.saved', 'services.show', ['id' => $id, 'lingua' => $locale],
        );
    }

    /** @return array<string, mixed> */
    private function data(Request $request): array
    {
        $data = [];
        foreach (['primary_category_id', 'booking', 'booking_url', 'cost_type', 'mediation', 'online_url', 'valid_from', 'valid_to', 'next_review_at', 'publication_status', 'main_site'] as $field) {
            $data[$field] = $request->string($field);
        }

        return [
            ...$data,
            'access_modes' => $this->values($request, 'access_modes'),
            'categories' => $this->ids($request, 'categories'),
            'needs' => $this->ids($request, 'needs'),
            'sites' => $this->ids($request, 'sites'),
            'languages' => $this->rows($request, 'languages'),
            'mark_verified' => $request->string('mark_verified') === '1',
        ];
    }

    /** @return array<string, mixed> */
    private function options(): array
    {
        return [
            'categories' => $this->management()->categories($this->view()->locale()),
            'needs' => $this->management()->needs($this->view()->locale()),
            'languages' => $this->management()->languages(),
            'locales' => $this->contentLocales(),
        ];
    }

    protected function editor(): ServiceEditor
    {
        return $this->container->get(ServiceEditor::class);
    }
}
