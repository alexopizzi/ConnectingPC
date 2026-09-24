<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Management\MediatorEditor;
use App\Http\Controllers\Manage\MediatorController as ManageMediatorController;
use App\Http\Request;
use App\Http\Response;

/** Mediatori in admin (permesso mediators.manage): elenco; la scheda è condivisa con l'area riservata. */
final class MediatorController extends ManageMediatorController
{
    protected function area(): string
    {
        return 'admin';
    }

    protected function organizationChoices(Request $request): array
    {
        return $this->management()->organizationOptions();
    }

    public function index(Request $request): Response
    {
        $filters = [
            'q' => mb_substr($request->string('q'), 0, 100),
            'visibility' => in_array($request->string('visibilita'), MediatorEditor::VISIBILITY, true) ? $request->string('visibilita') : '',
            'organization' => $request->int('organizzazione'),
        ];

        return $this->page('admin/mediators/index', [
            'pageTitle' => $this->t('admin.mediators.title'),
            'mediators' => $this->management()->mediators($filters),
            'filters' => $filters,
            'organizations' => $this->management()->organizationOptions(),
        ]);
    }
}
