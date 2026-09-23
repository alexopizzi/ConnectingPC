<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Request;
use App\Http\Response;

interface Middleware
{
    /**
     * @param callable(Request): Response $next
     * @param list<string> $arguments argomenti dal nome del middleware ("can:admin.access" → ["admin.access"])
     */
    public function handle(Request $request, callable $next, array $arguments = []): Response;
}
