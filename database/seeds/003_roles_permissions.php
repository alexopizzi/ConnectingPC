<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Container;
use App\Core\Database;

/*
 * Sincronizza permessi e ruoli di sistema da config/permissions.php.
 * I permessi dei ruoli di sistema sono riallineati al codice; i ruoli personalizzati non vengono toccati.
 */
return static function (Container $c): void {
    $db = $c->get(Database::class);
    $config = $c->get(Config::class);

    $db->transaction(static function (Database $db) use ($config): void {
        $catalog = (array) $config->get('permissions.permissions', []);
        foreach ($catalog as $code => $description) {
            $db->execute(
                'INSERT INTO permissions (code, description) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE description = VALUES(description)',
                [$code, $description],
            );
        }
        $permissionIds = [];
        foreach ($db->fetchAll('SELECT id, code FROM permissions') as $row) {
            $permissionIds[(string) $row['code']] = (int) $row['id'];
        }

        foreach ((array) $config->get('permissions.roles', []) as $code => $role) {
            $db->execute(
                'INSERT INTO roles (code, name, description, allowed_scopes, is_system) VALUES (?, ?, ?, ?, 1)
                 ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description),
                                         allowed_scopes = VALUES(allowed_scopes), is_system = 1',
                [$code, $role['name'], $role['description'], implode(',', $role['scopes'])],
            );
            $roleId = (int) $db->fetchValue('SELECT id FROM roles WHERE code = ?', [$code]);
            $codes = $role['permissions'] === '*' ? array_keys($catalog) : $role['permissions'];

            $db->execute('DELETE FROM role_permissions WHERE role_id = ?', [$roleId]);
            foreach ($codes as $permission) {
                if (!isset($permissionIds[$permission])) {
                    throw new RuntimeException("Permesso sconosciuto '$permission' nel ruolo $code");
                }
                $db->insert('role_permissions', ['role_id' => $roleId, 'permission_id' => $permissionIds[$permission]]);
            }
        }
    });
};
