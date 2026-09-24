<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Management\InboundRequestService;
use App\Http\Controllers\Manage\ManagementController;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use DomainException;

/** Registro delle richieste in ingresso (RF-37, permesso requests.manage anche sulla rotta). */
final class InboundRequestController extends ManagementController
{
    private const FIELDS = ['type', 'channel', 'organization_id', 'requester_name', 'requester_email', 'requester_phone', 'message', 'status', 'assigned_to', 'resolution_note'];

    protected function area(): string
    {
        return 'admin';
    }

    public function index(Request $request): Response
    {
        $filters = ['status' => $request->string('stato', 'open'), 'type' => $request->string('tipo'), 'q' => mb_substr($request->string('q'), 0, 100)];

        return $this->page('admin/requests/index', [
            'pageTitle' => $this->t('admin.requests.title'),
            'requests' => $this->service()->list($filters),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->page('admin/requests/edit', ['pageTitle' => $this->t('admin.requests.create'), 'item' => null, ...$this->options()]);
    }

    public function store(Request $request): Response
    {
        try {
            $id = $this->service()->create($this->user($request), $this->fields($request));
        } catch (DomainException $e) {
            return $this->backWithDomainError($request, 'requests.create', [], $e);
        }
        $this->flash('success', $this->t('manage.saved'));

        return $this->redirectTo('admin.requests.show', ['id' => $id]);
    }

    public function show(Request $request): Response
    {
        $item = $this->service()->find((int) $request->attribute('id')) ?? throw new HttpException(404);

        return $this->page('admin/requests/edit', ['pageTitle' => $this->t('admin.requests.item', ['id' => (int) $item['id']]), 'item' => $item, ...$this->options()]);
    }

    public function update(Request $request): Response
    {
        $id = (int) $request->attribute('id');

        return $this->attempt(fn () => $this->service()->update($this->user($request), $id, $this->fields($request)), 'manage.saved', 'requests.show', ['id' => $id]);
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

    /** @return array<string, mixed> */
    private function options(): array
    {
        return [
            'organizations' => $this->management()->organizationOptions(),
            'staff' => $this->db()->fetchAll(
                "SELECT DISTINCT u.id, u.display_name FROM users u JOIN role_assignments a ON a.user_id = u.id AND a.revoked_at IS NULL AND a.scope_type = 'global'
                  WHERE u.status = 'active' ORDER BY u.display_name"
            ),
        ];
    }

    private function service(): InboundRequestService
    {
        return $this->container->get(InboundRequestService::class);
    }
}
