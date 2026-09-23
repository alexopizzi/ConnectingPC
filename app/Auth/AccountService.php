<?php

declare(strict_types=1);

namespace App\Auth;

use App\Audit\AuditLogger;
use App\Core\Database;
use App\Domain\Users\UserRepository;
use App\Security\PasswordHasher;
use App\Security\PasswordPolicy;
use App\Security\RateLimiter;

/**
 * Attivazione da invito e recupero password (vault "50 - Autenticazione").
 */
final class AccountService
{
    public function __construct(
        private readonly Database $database,
        private readonly UserRepository $users,
        private readonly TokenService $tokens,
        private readonly PasswordHasher $hasher,
        private readonly PasswordPolicy $policy,
        private readonly RateLimiter $limiter,
        private readonly AccountMailer $mailer,
        private readonly AuditLogger $audit,
    ) {
    }

    /** Invia (o reinvia) l'invito a un utente non ancora attivo. */
    public function sendInvitation(int $userId): bool
    {
        $user = $this->users->find($userId);
        if ($user === null || $user['status'] !== 'invited') {
            return false;
        }
        $token = $this->tokens->issue($userId, 'invite', TokenService::INVITE_TTL);
        $this->audit->log('user.invitation_sent', 'user', $userId);

        return $this->mailer->invitation($user, $token);
    }

    /** @return array<string, mixed>|null utente dell'invito, se il token è valido */
    public function invitationUser(string $token): ?array
    {
        $found = $this->tokens->find($token, 'invite');
        $user = $found === null ? null : $this->users->find($found['user_id']);

        return $user !== null && $user['status'] === 'invited' ? $user : null;
    }

    /**
     * @return list<string> errori (chiavi di traduzione); vuoto se l'account è stato attivato
     */
    public function acceptInvitation(string $token, string $password, string $confirmation): array
    {
        $found = $this->tokens->find($token, 'invite');
        $user = $found === null ? null : $this->users->find($found['user_id']);
        if ($user === null || $user['status'] !== 'invited') {
            return ['auth.invite.invalid'];
        }
        $errors = $this->policy->validate($password, $confirmation, (string) $user['email']);
        if ($errors !== []) {
            return $errors;
        }

        $this->database->transaction(function () use ($found, $user, $password): void {
            $now = gmdate('Y-m-d H:i:s');
            $this->users->update((int) $user['id'], [
                'password_hash' => $this->hasher->hash($password),
                'status' => 'active',
                'email_verified_at' => $now,
                'password_changed_at' => $now,
            ]);
            $this->users->incrementSessionVersion((int) $user['id']);
            $this->tokens->markUsed($found['id']);
            $this->audit->log('user.activated', 'user', (int) $user['id'], null, ['user_id' => (int) $user['id']]);
        });

        return [];
    }

    /**
     * Richiesta di recupero: risposta sempre identica per non rivelare quali email sono registrate.
     * Restituisce false solo se la richiesta è stata bloccata dal rate limiting.
     */
    public function requestPasswordReset(string $email, string $ip): bool
    {
        $email = UserRepository::normalizeEmail($email);
        $ipBucket = $this->limiter->key('reset.ip', $ip);
        $emailBucket = $this->limiter->key('reset.email', $email);
        if ($this->limiter->tooManyAttempts($ipBucket, 10, 3600) || $this->limiter->tooManyAttempts($emailBucket, 3, 3600)) {
            return false;
        }
        $this->limiter->hit($ipBucket, 3600);
        $this->limiter->hit($emailBucket, 3600);

        $user = filter_var($email, FILTER_VALIDATE_EMAIL) ? $this->users->findByEmail($email) : null;
        if ($user !== null && $user['status'] === 'active') {
            $token = $this->tokens->issue((int) $user['id'], 'password_reset', TokenService::PASSWORD_RESET_TTL);
            $this->audit->log('user.password_reset_requested', 'user', (int) $user['id'], null, ['user_id' => (int) $user['id']]);
            $this->mailer->passwordReset($user, $token);
        }

        return true;
    }

    public function resetTokenIsValid(string $token): bool
    {
        return $this->tokens->find($token, 'password_reset') !== null;
    }

    /** @return list<string> errori; vuoto se la password è stata cambiata */
    public function resetPassword(string $token, string $password, string $confirmation): array
    {
        $found = $this->tokens->find($token, 'password_reset');
        $user = $found === null ? null : $this->users->find($found['user_id']);
        if ($user === null || $user['status'] !== 'active') {
            return ['auth.reset.invalid'];
        }
        $errors = $this->policy->validate($password, $confirmation, (string) $user['email']);
        if ($errors !== []) {
            return $errors;
        }

        $this->database->transaction(function () use ($found, $user, $password): void {
            $this->users->update((int) $user['id'], [
                'password_hash' => $this->hasher->hash($password),
                'password_changed_at' => gmdate('Y-m-d H:i:s'),
            ]);
            // Chiude tutte le sessioni aperte dell'utente (D-018).
            $this->users->incrementSessionVersion((int) $user['id']);
            $this->tokens->markUsed($found['id']);
            $this->audit->log('user.password_reset', 'user', (int) $user['id'], null, ['user_id' => (int) $user['id']]);
        });
        $this->mailer->passwordChanged($user);

        return [];
    }
}
