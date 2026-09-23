<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Auth\AccountService;
use App\Auth\LoginResult;
use App\Auth\LoginService;
use App\Auth\TokenService;
use App\Security\PasswordHasher;

final class AuthenticationTest extends DatabaseTestCase
{
    private function createUser(string $email, string $status = 'active', ?string $role = 'admin', string $scopeType = 'global', string $scopeKey = ''): int
    {
        $id = $this->db->insert('users', [
            'email' => $email,
            'display_name' => 'Test',
            'status' => $status,
            'password_hash' => $this->container->get(PasswordHasher::class)->hash('una frase lunga e sicura'),
        ]);
        if ($role !== null) {
            $roleId = (int) $this->db->fetchValue('SELECT id FROM roles WHERE code = ?', [$role]);
            $this->db->insert('role_assignments', ['user_id' => $id, 'role_id' => $roleId, 'scope_type' => $scopeType, 'scope_key' => $scopeKey]);
        }

        return $id;
    }

    public function testSuccessfulLoginAndGenericFailure(): void
    {
        $this->createUser('ok@example.test');
        $login = $this->container->get(LoginService::class);

        self::assertSame(LoginResult::Invalid, $login->attempt('nessuno@example.test', 'una frase lunga e sicura', '198.51.100.1'));
        self::assertSame(LoginResult::Invalid, $login->attempt('ok@example.test', 'sbagliata sbagliata', '198.51.100.1'));
        self::assertSame(LoginResult::Success, $login->attempt('OK@Example.test ', 'una frase lunga e sicura', '198.51.100.1'));
    }

    public function testThrottlingAfterFiveFailures(): void
    {
        $this->createUser('bersaglio@example.test');
        $login = $this->container->get(LoginService::class);

        for ($i = 0; $i < 5; $i++) {
            self::assertSame(LoginResult::Invalid, $login->attempt('bersaglio@example.test', 'errata numero ' . $i, '198.51.100.2'));
        }
        // Anche con la password giusta, dallo stesso IP, il tentativo è bloccato.
        self::assertSame(LoginResult::Throttled, $login->attempt('bersaglio@example.test', 'una frase lunga e sicura', '198.51.100.2'));
    }

    public function testInactiveAndUsersWithoutAccessAreRejected(): void
    {
        $this->createUser('sospeso@example.test', 'suspended');
        $this->createUser('senzaruolo@example.test', 'active', null);
        $typeId = (int) $this->db->fetchValue("SELECT id FROM organization_types WHERE code = 'association'");
        $orgId = $this->db->insert('organizations', ['name' => 'Org sospesa test', 'organization_type_id' => $typeId, 'access_status' => 'suspended']);
        $this->createUser('orgsospesa@example.test', 'active', 'org_referent', 'organization', (string) $orgId);
        $login = $this->container->get(LoginService::class);

        self::assertSame(LoginResult::Inactive, $login->attempt('sospeso@example.test', 'una frase lunga e sicura', '198.51.100.3'));
        self::assertSame(LoginResult::NoAccess, $login->attempt('senzaruolo@example.test', 'una frase lunga e sicura', '198.51.100.3'));
        self::assertSame(LoginResult::NoAccess, $login->attempt('orgsospesa@example.test', 'una frase lunga e sicura', '198.51.100.3'));
    }

    public function testPasswordResetTokenIsSingleUseAndInvalidatesSessions(): void
    {
        $id = $this->createUser('reset@example.test');
        $version = (int) $this->db->fetchValue('SELECT session_version FROM users WHERE id = ?', [$id]);
        $token = $this->container->get(TokenService::class)->issue($id, 'password_reset', TokenService::PASSWORD_RESET_TTL);
        $accounts = $this->container->get(AccountService::class);

        self::assertSame(['auth.password.mismatch'], $accounts->resetPassword($token, 'nuova frase molto lunga', 'diversa'));
        self::assertSame([], $accounts->resetPassword($token, 'nuova frase molto lunga', 'nuova frase molto lunga'));
        self::assertSame(['auth.reset.invalid'], $accounts->resetPassword($token, 'altra frase molto lunga', 'altra frase molto lunga'));
        self::assertSame($version + 1, (int) $this->db->fetchValue('SELECT session_version FROM users WHERE id = ?', [$id]));
        self::assertNull($this->db->fetchValue('SELECT token_hash FROM auth_tokens WHERE token_hash = ?', [$token]), 'Il token in chiaro non deve essere salvato');
    }

    public function testExpiredInvitationIsRejected(): void
    {
        $id = $this->createUser('invitato@example.test', 'invited');
        $token = $this->container->get(TokenService::class)->issue($id, 'invite', TokenService::INVITE_TTL);
        $this->db->execute('UPDATE auth_tokens SET expires_at = UTC_TIMESTAMP() - INTERVAL 1 MINUTE WHERE user_id = ?', [$id]);

        self::assertSame(['auth.invite.invalid'], $this->container->get(AccountService::class)
            ->acceptInvitation($token, 'una frase lunga e sicura', 'una frase lunga e sicura'));
    }
}
