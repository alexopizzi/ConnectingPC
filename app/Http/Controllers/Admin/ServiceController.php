<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Management\EditorialGuard;
use App\Http\Controllers\Manage\ServiceController as ManageServiceController;
use App\Http\Request;
use App\Http\Response;

/** Servizi in admin: elenco con filtri; la scheda è condivisa con l'area riservata. */
final class ServiceController extends ManageServiceController
{
    protected function area(): string
    {
        return 'admin';
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => mb_substr($request->string('q'), 0, 100),
            'publication' => in_array($request->string('pubblicazione'), EditorialGuard::PUBLICATION_STATUSES, true) ? $request->string('pubblicazione') : '',
            'category' => $request->int('categoria'),
            'organization' => $request->int('organizzazione'),
            'review_due' => $request->string('revisione') === '1',
        ];
        $page = max(1, $request->int('page', 1));
        $result = $this->management()->services($filters, self::PER_PAGE, ($page - 1) * self::PER_PAGE);

        return $this->page('admin/services/index', [
            'pageTitle' => $this->t('admin.services.title'),
            'services' => $result['rows'],
            'total' => $result['total'],
            'page' => $page,
            'pages' => max(1, (int) ceil($result['total'] / self::PER_PAGE)),
            'filters' => $filters,
            'categories' => array_values(array_filter($this->management()->categories(), static fn (array $c): bool => $c['parent'] === null)),
        ]);
    }
}
