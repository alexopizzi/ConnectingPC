<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Management\TaxonomyEditor;
use App\Http\Controllers\Manage\ManagementController;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use DomainException;

/** Tassonomie in admin (RF-31, permesso taxonomy.manage anche sulla rotta). */
final class TaxonomyController extends ManagementController
{
    protected function area(): string
    {
        return 'admin';
    }

    public function index(Request $request): Response
    {
        $counts = [];
        foreach (array_keys(TaxonomyEditor::TYPES) as $type) {
            $counts[$type] = count($this->editor()->list($type));
        }

        return $this->page('admin/taxonomy/index', ['pageTitle' => $this->t('admin.taxonomy.title'), 'counts' => $counts]);
    }

    public function list(Request $request): Response
    {
        $type = $this->type($request);

        return $this->page('admin/taxonomy/list', [
            'pageTitle' => $this->t('admin.taxonomy.' . $type),
            'type' => $type,
            'items' => $this->editor()->list($type),
            'locales' => $this->contentLocales(),
        ]);
    }

    public function create(Request $request): Response
    {
        $type = $this->type($request);

        return $this->page('admin/taxonomy/edit', ['pageTitle' => $this->t('admin.taxonomy.create'), 'type' => $type, 'item' => null, ...$this->options($type)]);
    }

    public function store(Request $request): Response
    {
        $type = $this->type($request);
        try {
            $id = $this->editor()->save($this->user($request), $type, null, $this->data($request));
        } catch (DomainException $e) {
            return $this->backWithDomainError($request, 'taxonomy.create', ['type' => $type], $e);
        }
        $this->flash('success', $this->t('manage.saved'));

        return $this->redirectTo('admin.taxonomy.edit', ['type' => $type, 'id' => $id]);
    }

    public function edit(Request $request): Response
    {
        $type = $this->type($request);
        $item = $this->editor()->find($type, (int) $request->attribute('id')) ?? throw new HttpException(404);

        return $this->page('admin/taxonomy/edit', ['pageTitle' => (string) ($item['labels']['it']['text'] ?? $item['code']), 'type' => $type, 'item' => $item, ...$this->options($type)]);
    }

    public function update(Request $request): Response
    {
        $type = $this->type($request);
        $id = (int) $request->attribute('id');

        return $this->attempt(fn () => $this->editor()->save($this->user($request), $type, $id, $this->data($request)), 'manage.saved', 'taxonomy.edit', ['type' => $type, 'id' => $id]);
    }

    /** @return array<string, mixed> */
    private function data(Request $request): array
    {
        $data = [];
        foreach (['code', 'sort_order', 'icon', 'parent_id', 'kind', 'is_active', 'is_featured', 'is_public_body', 'approve'] as $field) {
            $data[$field] = $request->string($field);
        }
        $data['labels'] = array_map(static fn ($v): string => is_scalar($v) ? (string) $v : '', (array) ($request->body['labels'] ?? []));
        if (array_key_exists('categories_sent', $request->body)) {
            $data['categories'] = $this->ids($request, 'categories');
        }
        if (array_key_exists('countries', $request->body)) {
            $data['countries'] = preg_split('/[\s,;]+/', strtoupper($request->string('countries')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function options(string $type): array
    {
        return [
            'locales' => $this->contentLocales(),
            'icons' => array_keys(require APP_BASE_PATH . '/config/icons.php'),
            'areas' => $type === 'categories' ? array_values(array_filter($this->management()->categories(), static fn (array $c): bool => $c['parent'] === null)) : [],
            'categories' => $type === 'needs' ? $this->management()->categories() : [],
        ];
    }

    private function type(Request $request): string
    {
        $type = (string) $request->attribute('type');

        return isset(TaxonomyEditor::TYPES[$type]) ? $type : throw new HttpException(404);
    }

    private function editor(): TaxonomyEditor
    {
        return $this->container->get(TaxonomyEditor::class);
    }
}
