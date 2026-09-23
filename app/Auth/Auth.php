<?php

declare(strict_types=1);

namespace App\Auth;

use App\Audit\AuditLogger;
use App\Core\Session;
use App\Domain\Users\UserRepository;
use App\Security\Csrf;

/**
 * Stato di autenticazione della richiesta (sessione) — vault "50 - Autenticazione", D-018.
 * Controlla a ogni richiesta: utente attivo, session_version invariata, inattività e durata massima.
 */
final class Auth
{
    private const KEY_USER = 'auth.user_id';
    private const KEY_VERSION = 'auth.session_version';
    private const KEY_LOGIN_AT = 'auth.login_at';
    private const KEY_ACTIVITY = 'auth.last_activity';
    private const KEY_ORGANIZATION = 'auth.organization_id';
    private const KEY_INTENDED = 'auth.intended';

    /** @var array<string, mixed>|null */
    private ?array $user = null;
    private bool $resolved = false;

    public function __construct(
        private readonly Session $session,
        private readonly UserRepository $users,
        private readonly Csrf $csrf,
        private readonly AuditLogger $audit,
        private readonly int $absoluteHours,
    ) {
    }

    /**
     * Utente autenticato, oppure null. $idleMinutes: inattività massima per l'area corrente.
     *
     * @return array<string, mixed>|null
     */
    public function user(int $idleMinutes = 60): ?array
    {
        if ($this->resolved) {
            return $this->user;
        }
        $this->resolved = true;

        if (!$this->session->isStarted()) {
            return null;
        }
        $userId = $this->session->get(self::KEY_USER);
        if (!is_int($userId)) {
            return null;
        }

        $now = time();
        $user = $this->users->find($userId);
        $valid = $user !== null
            && $user['status'] === 'active'
            && (int) $user['session_version'] === (int) $this->session->get(self::KEY_VERSION)
            && $now - (int) $this->session->get(self::KEY_ACTIVITY, 0) <= $idleMinutes * 60
            && $now - (int) $this->session->get(self::KEY_LOGIN_AT, 0) <= $this->absoluteHours * 3600;

        if (!$valid) {
            $this->clearSession(expired: true);

            return null;
        }

        $this->session->set(self::KEY_ACTIVITY, $now);
        $this->user = $user;
        $this->audit->setActor((int) $user['id'], $this->activeOrganizationId());

        return $user;
    }

    public function id(): ?int
    {
        return $this->user === null ? null : (int) $this->user['id'];
    }

    /** @param array<string, mixed> $user */
    public function login(array $user): void
    {
        $this->session->regenerate();
        $this->csrf->rotate();
        $now = time();
        $this->session->set(self::KEY_USER, (int) $user['id']);
        $this->session->set(self::KEY_VERSION, (int) $user['session_version']);
        $this->session->set(self::KEY_LOGIN_AT, $now);
        $this->session->set(self::KEY_ACTIVITY, $now);
        $this->session->forget(self::KEY_ORGANIZATION);

        $this->users->update((int) $user['id'], ['last_login_at' => gmdate('Y-m-d H:i:s')]);
        $this->user = $user;
        $this->resolved = true;
        $this->audit->setActor((int) $user['id']);
        $this->audit->log('auth.login', 'user', (int) $user['id']);
    }

    public function logout(): void
    {
        if ($this->user !== null) {
            $this->audit->log('auth.logout', 'user', (int) $this->user['id']);
        }
        $this->clearSession(expired: false);
    }

    public function activeOrganizationId(): ?int
    {
        $id = $this->session->isStarted() ? $this->session->get(self::KEY_ORGANIZATION) : null;

        return is_int($id) ? $id : null;
    }

    public function setActiveOrganization(?int $organizationId): void
    {
        $this->session->set(self::KEY_ORGANIZATION, $organizationId);
        $this->audit->setActor($this->id(), $organizationId);
    }

    /** Percorso interno da riaprire dopo il login (mai URL esterni: niente open redirect). */
    public function rememberIntended(string $path): void
    {
        if (str_starts_with($path, '/') && !str_starts_with($path, '//') && !str_contains($path, '\\')) {
            $this->session->set(self::KEY_INTENDED, $path);
        }
    }

    public function pullIntended(): ?string
    {
        $path = $this->session->pull(self::KEY_INTENDED);

        return is_string($path) ? $path : null;
    }

    public function sessionExpiredFlag(): bool
    {
        return (bool) $this->session->getFlash('auth.expired', false);
    }

    private function clearSession(bool $expired): void
    {
        foreach ([self::KEY_USER, self::KEY_VERSION, self::KEY_LOGIN_AT, self::KEY_ACTIVITY, self::KEY_ORGANIZATION] as $key) {
            $this->session->forget($key);
        }
        $this->session->regenerate();
        $this->csrf->rotate();
        if ($expired) {
            $this->session->flash('auth.expired', true);
        }
        $this->user = null;
        $this->audit->setActor(null);
    }
}
