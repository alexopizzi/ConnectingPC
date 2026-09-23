<?php

declare(strict_types=1);

namespace App\Auth;

use App\Audit\AuditLogger;
use App\Authorization\AuthorizationData;
use App\Authorization\Gate;
use App\Domain\Users\UserRepository;
use App\Security\PasswordHasher;
use App\Security\RateLimiter;

/**
 * Tentativo di login con rate limiting (vault "50", "72"):
 * 5 tentativi falliti / 15 min per coppia account+IP, 20 per account, 30 per IP.
 */
final class LoginService
{
    private const WINDOW = 900;
    private const MAX_PER_ACCOUNT_IP = 5;
    private const MAX_PER_ACCOUNT = 20;
    private const MAX_PER_IP = 30;

    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly RateLimiter $limiter,
        private readonly Gate $gate,
        private readonly AuthorizationData $authorization,
        private readonly Auth $auth,
        private readonly AuditLogger $audit,
    ) {
    }

    public function attempt(string $email, string $password, string $ip): LoginResult
    {
        $email = UserRepository::normalizeEmail($email);
        $buckets = [
            [$this->limiter->key('login.account_ip', $email, $ip), self::MAX_PER_ACCOUNT_IP],
            [$this->limiter->key('login.account', $email), self::MAX_PER_ACCOUNT],
            [$this->limiter->key('login.ip', $ip), self::MAX_PER_IP],
        ];
        foreach ($buckets as [$bucket, $max]) {
            if ($this->limiter->tooManyAttempts($bucket, $max, self::WINDOW)) {
                return LoginResult::Throttled;
            }
        }

        $user = $email === '' ? null : $this->users->findByEmail($email);
        if (!$this->hasher->verify($password, $user['password_hash'] ?? null)) {
            foreach ($buckets as [$bucket]) {
                $this->limiter->hit($bucket, self::WINDOW);
            }
            if ($user !== null) {
                $this->audit->log('auth.login_failed', 'user', (int) $user['id']);
            }

            return LoginResult::Invalid;
        }

        if ($user['status'] !== 'active') {
            return LoginResult::Inactive;
        }
        if (!$this->hasAnyAccess($user)) {
            return LoginResult::NoAccess;
        }

        $this->limiter->clear($buckets[0][0]);
        if ($this->hasher->needsRehash((string) $user['password_hash'])) {
            $this->users->update((int) $user['id'], ['password_hash' => $this->hasher->hash($password)]);
        }
        $this->auth->login($user);

        return LoginResult::Success;
    }

    /** @param array<string, mixed> $user */
    private function hasAnyAccess(array $user): bool
    {
        if ($this->authorization->assignments((int) $user['id']) === []) {
            return false;
        }

        return $this->gate->allows($user, 'admin.access') || $this->gate->allows($user, 'portal.access');
    }
}
