<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Manage\SiteController as ManageSiteController;

/** Sedi della propria organizzazione nell'area riservata. */
final class SiteController extends ManageSiteController
{
    protected function area(): string
    {
        return 'portal';
    }
}
