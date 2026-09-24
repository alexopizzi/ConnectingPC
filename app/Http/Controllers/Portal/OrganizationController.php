<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Manage\OrganizationController as ManageOrganizationController;

/** Scheda della propria organizzazione nell'area riservata (D-022: pubblicazione diretta dei propri dati). */
final class OrganizationController extends ManageOrganizationController
{
    protected function area(): string
    {
        return 'portal';
    }
}
