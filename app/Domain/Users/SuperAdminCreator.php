<?php

declare(strict_types=1);

namespace App\Domain\Users;

use App\Audit\AuditLogger;
use App\Auth\TokenService;
use App\Core\Database;
use App\Core\UrlGenerator;
use DomainException;
use RuntimeException;

/**
 * Creazione di un super amministratore con link di attivazione (invito, 72 ore, uso singolo).
 * Usata da `user:create-admin` e dalla pagina di manutenzione per il primo accesso in produzione.
 */
final class SuperAdminCreator
{
    public function __construct(
        private readonly Database $database,
        private readonly AuditLogger $audit,
        private readonly TokenService $tokens,
        private readonly UrlGenerator $url,
    ) {
    }

    public function exists(): bool
    {
        return (int) $this->database->fetchValue(
            "SELECT COUNT(*) FROM role_assignments ra JOIN roles r ON r.id = ra.role_id JOIN users u ON u.id = ra.user_id
              WHERE r.code = 'super_admin' AND u.status IN ('active', 'invited')"
        ) > 0;
    }

    /** @return string link assoluto di attivazione (segreto: va trasmesso solo all'interessato) */
    public function create(string $email, string $name, string $locale, string $via): string
    {
        $email = UserRepository::normalizeEmail($email);
        $name = trim($name);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || $name === '' || mb_strlen($name) > 150) {
            throw new DomainException('Email o nome non validi.');
        }
        if ($this->database->fetchValue('SELECT id FROM users WHERE email = ?', [$email]) !== null) {
            throw new DomainException('Esiste già un utente con questa email.');
        }

        $token = $this->database->transaction(function (Database $db) use ($email, $name, $locale, $via): string {
            $roleId = (int) $db->fetchValue("SELECT id FROM roles WHERE code = 'super_admin'");
            if ($roleId === 0) {
                throw new RuntimeException('Ruoli non presenti: eseguire prima l’installazione (setup).');
            }
            $id = $db->insert('users', ['email' => $email, 'display_name' => $name, 'preferred_locale' => $locale, 'status' => 'invited']);
            $db->insert('role_assignments', ['user_id' => $id, 'role_id' => $roleId, 'scope_type' => 'global', 'scope_key' => '']);
            $this->audit->log('user.created', 'user', $id, ['email' => $email, 'role' => 'super_admin', 'via' => $via]);

            return $this->tokens->issue($id, 'invite', TokenService::INVITE_TTL);
        });

        return $this->url->absoluteRoute('auth.invitation', ['locale' => $locale, 'token' => $token]);
    }
}
