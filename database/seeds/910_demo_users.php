<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Container;
use App\Core\Database;
use App\Security\PasswordHasher;

/*
 * UTENTI DIMOSTRATIVI (solo `seed --demo`, MAI in produzione): uno per ruolo, con password nota,
 * per provare accessi e permessi. Indirizzi nel dominio riservato .test (non ricevono email).
 */
return static function (Container $c): void {
    if (App::environment() === 'production') {
        throw new RuntimeException('I dati dimostrativi non si caricano in produzione.');
    }

    $db = $c->get(Database::class);
    // Password comune a tutti gli utenti dimostrativi (documentata in README e nel vault, nota 80).
    $password = $c->get(PasswordHasher::class)->hash('Prova-ConnectingPC-2026');
    $organization = static fn (string $name): string => (string) $db->fetchValue('SELECT id FROM organizations WHERE name = ?', [$name]);

    $users = [
        ['superadmin@connectingpc.test', 'Super Amministratore Demo', 'super_admin', 'global', ''],
        ['admin@connectingpc.test', 'Amministratore Demo', 'admin', 'global', ''],
        ['redattore@connectingpc.test', 'Redattore Demo', 'editor', 'global', ''],
        ['traduttore.ar@connectingpc.test', 'Traduttore Arabo Demo', 'translator', 'locale', 'ar'],
        ['referente.ponte@connectingpc.test', 'Referente Ponte Demo', 'org_referent', 'organization', $organization('Associazione Esempio Ponte')],
        ['utente.orizzonti@connectingpc.test', 'Utente Orizzonti Demo', 'org_user', 'organization', $organization('Cooperativa Esempio Orizzonti')],
        ['referente.sospeso@connectingpc.test', 'Referente Centro Sospeso Demo', 'org_referent', 'organization', $organization('Centro Esempio Sospeso')],
    ];

    foreach ($users as [$email, $name, $role, $scopeType, $scopeKey]) {
        $userId = $db->fetchValue('SELECT id FROM users WHERE email = ?', [$email]);
        if ($userId === null) {
            $userId = $db->insert('users', [
                'email' => $email,
                'display_name' => $name,
                'preferred_locale' => 'it',
                'status' => 'active',
                'password_hash' => $password,
                'email_verified_at' => gmdate('Y-m-d H:i:s'),
                'password_changed_at' => gmdate('Y-m-d H:i:s'),
            ]);
        }
        $roleId = (int) $db->fetchValue('SELECT id FROM roles WHERE code = ?', [$role]);
        $db->execute(
            'INSERT IGNORE INTO role_assignments (user_id, role_id, scope_type, scope_key) VALUES (?, ?, ?, ?)',
            [(int) $userId, $roleId, $scopeType, $scopeKey],
        );
    }
};
