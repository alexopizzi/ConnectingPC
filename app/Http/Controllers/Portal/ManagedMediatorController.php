<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Authorization\Gate;
use App\Http\Controllers\Manage\MediatorController as ManageMediatorController;
use App\Http\Request;

/**
 * Mediatori collegati alla propria organizzazione, gestiti dall'area riservata (org.mediators.edit).
 * Da non confondere con Portal\MediatorController, l'elenco dei mediatori per gli operatori.
 */
final class ManagedMediatorController extends ManageMediatorController
{
    protected function area(): string
    {
        return 'portal';
    }

    protected function organizationChoices(Request $request): array
    {
        return $this->management()->organizationOptions(
            $this->container->get(Gate::class)->organizationsWith($this->user($request), 'org.mediators.edit'),
        );
    }
}
