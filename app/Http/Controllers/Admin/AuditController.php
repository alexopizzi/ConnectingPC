<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;

/** Consultazione dell'audit log con filtri (permesso audit.view). */
final class AuditController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request): Response
    {
        $where = ['1 = 1'];
        $params = [];
        $action = mb_substr($request->string('action'), 0, 64);
        if ($action !== '') {
            $where[] = 'l.action LIKE ?';
            $params[] = addcslashes($action, '%_\\') . '%';
        }
        $entity = $request->string('entity_type');
        if ($entity !== '' && preg_match('/^[a-z_]{1,40}$/', $entity)) {
            $where[] = 'l.entity_type = ?';
            $params[] = $entity;
        }
        $entityId = $request->int('entity_id');
        if ($entityId > 0) {
            $where[] = 'l.entity_id = ?';
            $params[] = $entityId;
        }
        $userId = $request->int('user_id');
        if ($userId > 0) {
            $where[] = 'l.user_id = ?';
            $params[] = $userId;
        }
        $condition = implode(' AND ', $where);
        $page = max(1, $request->int('page', 1));

        $total = (int) $this->db()->fetchValue("SELECT COUNT(*) FROM audit_log l WHERE $condition", $params);
        $rows = $this->db()->fetchAll(
            "SELECT l.id, l.occurred_at, l.action, l.entity_type, l.entity_id, l.changes, l.request_id,
                    u.display_name AS user_name, l.user_id, o.name AS organization_name, a.display_name AS approver_name
               FROM audit_log l
               LEFT JOIN users u ON u.id = l.user_id
               LEFT JOIN users a ON a.id = l.approved_by
               LEFT JOIN organizations o ON o.id = l.organization_id
              WHERE $condition
              ORDER BY l.id DESC
              LIMIT ? OFFSET ?",
            [...$params, self::PER_PAGE, ($page - 1) * self::PER_PAGE],
        );

        return $this->render('admin/audit/index', [
            'pageTitle' => $this->t('admin.audit.title'),
            'rows' => $rows,
            'total' => $total,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / self::PER_PAGE)),
            'filters' => [
                'action' => $action,
                'entity_type' => $entity,
                'entity_id' => $entityId > 0 ? (string) $entityId : '',
                'user_id' => $userId > 0 ? (string) $userId : '',
            ],
        ], 'layouts/admin');
    }
}
