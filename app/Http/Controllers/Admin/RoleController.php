<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;

/** Matrice ruoli × permessi in sola lettura (i ruoli di sistema si modificano in config/permissions.php). */
final class RoleController extends Controller
{
    public function index(Request $request): Response
    {
        $roles = $this->db()->fetchAll('SELECT id, code, name, description, allowed_scopes FROM roles ORDER BY id');
        $permissions = $this->db()->fetchAll('SELECT id, code, description FROM permissions ORDER BY code');
        $matrix = [];
        foreach ($this->db()->fetchAll('SELECT role_id, permission_id FROM role_permissions') as $row) {
            $matrix[(int) $row['role_id']][(int) $row['permission_id']] = true;
        }

        return $this->render('admin/roles/index', [
            'pageTitle' => $this->t('admin.roles.title'),
            'roles' => $roles,
            'permissions' => $permissions,
            'matrix' => $matrix,
        ], 'layouts/admin');
    }
}
