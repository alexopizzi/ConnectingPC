<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Authorization\AuthorizationData;
use App\Authorization\Gate;
use App\Authorization\ResourceScope;
use PHPUnit\Framework\TestCase;

/**
 * Matrice delle autorizzazioni (vault "51"): ambito, stato dell'organizzazione, politica di pubblicazione.
 */
final class GateTest extends TestCase
{
    private const EDIT = ['org.services.edit', 'content.publish', 'translations.approve'];
    private const PUBLISH = ['content.publish', 'translations.approve'];

    /** @param array<int, array<string, mixed>> $organizations */
    private function gate(array $assignments, array $organizations, string $default = 'direct'): Gate
    {
        $data = new class ($assignments, $organizations) implements AuthorizationData {
            public function __construct(private array $assignments, private array $organizations)
            {
            }

            public function assignments(int $userId): array
            {
                return $this->assignments[$userId] ?? [];
            }

            public function organization(int $organizationId): ?array
            {
                return $this->organizations[$organizationId] ?? null;
            }
        };

        return new Gate($data, $default, self::EDIT, self::PUBLISH);
    }

    private static function assignment(string $role, string $scopeType, string $scopeKey, array $permissions): array
    {
        return ['assignment_id' => 1, 'role' => $role, 'scope_type' => $scopeType, 'scope_key' => $scopeKey, 'permissions' => $permissions];
    }

    private static function org(string $access = 'enabled', bool $edit = true, ?string $policy = null): array
    {
        return ['access_status' => $access, 'portal_edit_enabled' => $edit, 'publication_policy' => $policy];
    }

    public function testGlobalRoleAllowsEverywhereButInactiveUsersNothing(): void
    {
        $gate = $this->gate([1 => [self::assignment('admin', 'global', '', ['users.manage'])]], []);

        self::assertTrue($gate->allows(['id' => 1, 'status' => 'active'], 'users.manage'));
        self::assertFalse($gate->allows(['id' => 1, 'status' => 'suspended'], 'users.manage'));
        self::assertFalse($gate->allows(['id' => 1, 'status' => 'active'], 'audit.view'));
        self::assertFalse($gate->allows(null, 'users.manage'));
    }

    public function testOrganizationUserCannotTouchOtherOrganizations(): void
    {
        $gate = $this->gate(
            [2 => [self::assignment('org_user', 'organization', '10', ['org.services.edit', 'content.publish'])]],
            [10 => self::org(), 20 => self::org()],
        );
        $user = ['id' => 2, 'status' => 'active'];

        self::assertTrue($gate->allows($user, 'org.services.edit', ResourceScope::organization(10)));
        self::assertFalse($gate->allows($user, 'org.services.edit', ResourceScope::organization(20)));
        self::assertSame([10], $gate->organizationsWith($user, 'org.services.edit'));
    }

    public function testSuspendedOrganizationOrDisabledEditingBlocksAccess(): void
    {
        $user = ['id' => 3, 'status' => 'active'];
        $assignments = [3 => [self::assignment('org_referent', 'organization', '10', ['org.services.edit', 'restricted.view'])]];

        $suspended = $this->gate($assignments, [10 => self::org('suspended')]);
        self::assertFalse($suspended->allows($user, 'restricted.view', ResourceScope::organization(10)));

        $noEdit = $this->gate($assignments, [10 => self::org('enabled', false)]);
        self::assertFalse($noEdit->allows($user, 'org.services.edit', ResourceScope::organization(10)));
        self::assertTrue($noEdit->allows($user, 'restricted.view', ResourceScope::organization(10)));
    }

    public function testPublicationPolicyDirectByDefaultAndReviewAsException(): void
    {
        $user = ['id' => 4, 'status' => 'active'];
        $assignments = [4 => [self::assignment('org_referent', 'organization', '10', ['content.publish'])]];

        self::assertTrue($this->gate($assignments, [10 => self::org()])->allows($user, 'content.publish', ResourceScope::organization(10)));
        self::assertFalse($this->gate($assignments, [10 => self::org(policy: 'review')])->allows($user, 'content.publish', ResourceScope::organization(10)));
        self::assertFalse($this->gate($assignments, [10 => self::org()], 'review')->allows($user, 'content.publish', ResourceScope::organization(10)));
    }

    public function testLocaleScopeForTranslators(): void
    {
        $gate = $this->gate([5 => [self::assignment('translator', 'locale', 'ar', ['translations.approve'])]], []);
        $user = ['id' => 5, 'status' => 'active'];

        self::assertTrue($gate->allows($user, 'translations.approve', new ResourceScope(locale: 'ar')));
        self::assertFalse($gate->allows($user, 'translations.approve', new ResourceScope(locale: 'fr')));
        self::assertFalse($gate->allows($user, 'translations.approve'));
    }
}
