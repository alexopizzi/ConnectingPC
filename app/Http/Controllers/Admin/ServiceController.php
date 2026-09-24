<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Management\ServiceEditor;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use DomainException;

/** Servizi in admin: elenco con filtri, creazione dalla scheda dell'organizzazione, dati e testi tradotti. */
final class ServiceController extends ManagementController
{
    public function index(Request $request): Response
    {
        $filters = [
            'q' => mb_substr($request->string('q'), 0, 100),
            'publication' => in_array($request->string('pubblicazione'), ['draft', 'in_review', 'published', 'archived'], true) ? $request->string('pubblicazione') : '',
            'category' => $request->int('categoria'),
            'organization' => $request->int('organizzazione'),
            'review_due' => $request->string('revisione') === '1',
        ];
        $page = max(1, $request->int('page', 1));
        $result = $this->management()->services($filters, self::PER_PAGE, ($page - 1) * self::PER_PAGE);

        return $this->page('admin/services/index', [
            'pageTitle' => $this->t('admin.services.title'),
            'services' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'pages' => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'filters' => $filters,
            'categories' => array_values(array_filter($this->management()->categories(), static fn (array $c): bool => $c['parent'] === null)),
        ]);
    }

    public function create(Request $request): Response
    {
        $organization = $this->management()->organization((int) $request->attribute('id')) ?? throw new HttpException(404);

        return $this->page('admin/services/edit', [
            'pageTitle' => $this->t('admin.services.create'),
            'organization' => $organization,
            'service' => null,
            'organizationSites' => $this->management()->organizationSites((int) $organization['id']),
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
            return $this->backWithErrors($request, $this->view()->route('admin.services.create', ['id' => $organizationId]), ['form' => [$this->t($e->getMessage())]]);
        }
        $this->flash('success', $this->t('manage.saved'));

        return $this->redirectTo('admin.services.show', ['id' => $id]);
    }

    public function show(Request $request): Response
    {
        $service = $this->management()->service((int) $request->attribute('id')) ?? throw new HttpException(404);
        $organization = $this->management()->organization((int) $service['organization_id']) ?? throw new HttpException(404);

        return $this->page('admin/services/edit', [
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

        return $this->attempt(fn () => $this->editor()->update($this->user($request), $id, $this->data($request)), 'manage.saved', 'admin.services.show', ['id' => $id]);
    }

    public function saveTexts(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $locale = $request->string('locale');

        return $this->attempt(
            fn () => $this->editor()->saveTexts($this->user($request), $id, $locale, $this->texts($request, ServiceEditor::TEXT_FIELDS), $request->string('approve') === '1'),
            'manage.saved', 'admin.services.show', ['id' => $id, 'lingua' => $locale],
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
            'categories' => $this->management()->categories(),
            'needs' => $this->management()->needs(),
            'languages' => $this->management()->languages(),
            'locales' => $this->contentLocales(),
        ];
    }

    private function editor(): ServiceEditor
    {
        return $this->container->get(ServiceEditor::class);
    }
}
