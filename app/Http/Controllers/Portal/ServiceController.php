<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Manage\ServiceController as ManageServiceController;

/** Servizi della propria organizzazione nell'area riservata. */
final class ServiceController extends ManageServiceController
{
    protected function area(): string
    {
        return 'portal';
    }
}
