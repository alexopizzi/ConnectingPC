<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;

final class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $db = $this->db();
        $stats = [
            'users_active' => (int) $db->fetchValue("SELECT COUNT(*) FROM users WHERE status = 'active'"),
            'users_invited' => (int) $db->fetchValue("SELECT COUNT(*) FROM users WHERE status = 'invited'"),
            'organizations' => (int) $db->fetchValue('SELECT COUNT(*) FROM organizations'),
            'organizations_enabled' => (int) $db->fetchValue("SELECT COUNT(*) FROM organizations WHERE access_status = 'enabled'"),
        ];
        $recent = $db->fetchAll(
            'SELECT l.occurred_at, l.action, l.entity_type, l.entity_id, u.display_name
               FROM audit_log l LEFT JOIN users u ON u.id = l.user_id
              ORDER BY l.id DESC LIMIT 10'
        );

        return $this->render('admin/dashboard', [
            'pageTitle' => $this->t('admin.dashboard.title'),
            'stats' => $stats,
            'recent' => $recent,
        ], 'layouts/admin');
    }
}
