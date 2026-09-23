<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Auth\AccountService;
use App\Authorization\Gate;
use App\Core\Validator;
use App\Domain\Users\UserRepository;
use App\Domain\Users\UserService;
use App\Http\Controller;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\I18n\LocaleRegistry;
use DomainException;

/** Gestione utenti in admin (permesso users.manage, verificato anche nei servizi di dominio). */
final class UserController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request): Response
    {
        $status = $request->string('status');
        $status = in_array($status, ['invited', 'active', 'suspended', 'disabled'], true) ? $status : null;
        $page = max(1, $request->int('page', 1));
        $result = $this->container->get(UserRepository::class)
            ->search(mb_substr($request->string('q'), 0, 100), $status, self::PER_PAGE, ($page - 1) * self::PER_PAGE);

        return $this->render('admin/users/index', [
            'pageTitle' => $this->t('admin.users.title'),
            'users' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'pages' => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'filters' => ['q' => $request->string('q'), 'status' => $status ?? ''],
        ], 'layouts/admin');
    }

    public function create(Request $request): Response
    {
        return $this->render('admin/users/create', [
            'pageTitle' => $this->t('admin.users.create_title'),
            ...$this->formOptions($request),
        ], 'layouts/admin');
    }

    public function store(Request $request): Response
    {
        $back = $this->view()->route('admin.users.create');
        $errors = Validator::validate($request->body, [
            'email' => ['required', 'email', 'max:254'],
            'display_name' => ['required', 'max:120'],
            'preferred_locale' => ['required', 'max:12'],
            'role' => ['required', 'max:50'],
            'scope_type' => ['required', 'in:global|organization|locale'],
        ]);
        if ($errors !== []) {
            return $this->backWithErrors($request, $back, $this->translateErrors($errors));
        }

        $scopeType = $request->string('scope_type');
        $scopeKey = match ($scopeType) {
            'organization' => $request->string('organization_id'),
            'locale' => $request->string('scope_locale'),
            default => '',
        };
        $locale = $request->string('preferred_locale');
        $locale = $this->container->get(LocaleRegistry::class)->isEnabled($locale) ? $locale : null;

        try {
            $id = $this->container->get(UserService::class)->create(
                $this->user($request),
                $request->string('email'),
                $request->string('display_name'),
                $locale,
                $request->string('role'),
                $scopeType,
                $scopeKey,
            );
        } catch (DomainException $e) {
            return $this->backWithErrors($request, $back, ['form' => [$this->t($e->getMessage())]]);
        }

        $sent = $this->container->get(AccountService::class)->sendInvitation($id);
        $this->flash($sent ? 'success' : 'warning', $this->t($sent ? 'admin.users.invited' : 'admin.users.invite_failed'));

        return $this->redirectTo('admin.users.show', ['id' => $id]);
    }

    public function show(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $users = $this->container->get(UserRepository::class);
        $target = $users->find($id) ?? throw new HttpException(404);

        return $this->render('admin/users/show', [
            'pageTitle' => (string) $target['display_name'],
            'target' => $target,
            'assignments' => $users->assignments($id),
            ...$this->formOptions($request),
        ], 'layouts/admin');
    }

    public function updateStatus(Request $request): Response
    {
        $id = (int) $request->attribute('id');

        return $this->run($request, $id, fn () => $this->container->get(UserService::class)
            ->setStatus($this->user($request), $id, $request->string('status')), 'admin.users.status_updated');
    }

    public function resendInvitation(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $this->container->get(Gate::class)->authorize($this->user($request), 'users.manage');
        $sent = $this->container->get(AccountService::class)->sendInvitation($id);
        $this->flash($sent ? 'success' : 'warning', $this->t($sent ? 'admin.users.invited' : 'admin.users.invite_failed'));

        return $this->redirectTo('admin.users.show', ['id' => $id]);
    }

    public function assignRole(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $scopeType = $request->string('scope_type');
        $scopeKey = match ($scopeType) {
            'organization' => $request->string('organization_id'),
            'locale' => $request->string('scope_locale'),
            default => '',
        };

        return $this->run($request, $id, fn () => $this->container->get(UserService::class)
            ->assignRole($this->user($request), $id, $request->string('role'), $scopeType, $scopeKey), 'admin.users.role_granted');
    }

    public function revokeRole(Request $request): Response
    {
        $id = (int) $request->attribute('id');
        $assignmentId = (int) $request->attribute('assignment');

        return $this->run($request, $id, fn () => $this->container->get(UserService::class)
            ->revokeAssignment($this->user($request), $id, $assignmentId), 'admin.users.role_revoked');
    }

    private function run(Request $request, int $id, callable $operation, string $successKey): Response
    {
        try {
            $operation();
            $this->flash('success', $this->t($successKey));
        } catch (DomainException $e) {
            $this->flash('error', $this->t($e->getMessage()));
        }

        return $this->redirectTo('admin.users.show', ['id' => $id]);
    }

    /** @return array<string, mixed> */
    private function formOptions(Request $request): array
    {
        $gate = $this->container->get(Gate::class);
        $canProtected = $gate->allows($this->user($request), 'roles.manage');
        $roles = array_values(array_filter(
            $this->db()->fetchAll('SELECT code, name, allowed_scopes FROM roles ORDER BY id'),
            static fn (array $r): bool => $canProtected || !in_array($r['code'], ['super_admin', 'admin'], true),
        ));

        return [
            'roles' => $roles,
            'organizations' => $this->db()->fetchAll('SELECT id, name, access_status FROM organizations ORDER BY name'),
            'locales' => $this->container->get(LocaleRegistry::class)->enabled(),
        ];
    }
}
