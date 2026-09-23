<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Auth\Auth;
use App\Core\App;
use App\Core\Config;
use App\Core\Container;
use App\Core\Env;
use App\Core\View;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

/**
 * Richiede un utente autenticato. Argomento: area ("admin" o "portal") per la scadenza per inattività;
 * per l'area admin applica anche l'eventuale allowlist di IP (ADMIN_IP_ALLOWLIST).
 */
final class Authenticate implements Middleware
{
    public function __construct(private readonly Container $container)
    {
    }

    public function handle(Request $request, callable $next, array $arguments = []): Response
    {
        $area = $arguments[0] ?? 'portal';
        if ($area === 'admin' && !$this->ipAllowed($request->ip())) {
            throw new HttpException(403);
        }

        $config = $this->container->get(Config::class);
        $idle = (int) $config->get($area === 'admin' ? 'app.session.admin_idle_minutes' : 'app.session.idle_minutes', 60);
        $auth = $this->container->get(Auth::class);
        $user = $auth->user($idle);

        if ($user === null) {
            if (!$request->isWrite()) {
                $auth->rememberIntended(App::baseUrl() . $request->path . ($request->query === [] ? '' : '?' . http_build_query($request->query)));
            }
            $view = $this->container->get(View::class);

            return Response::redirect($view->route('auth.login', ['locale' => $view->locale()]));
        }

        $request->attributes['user'] = $user;
        $view = $this->container->get(View::class);
        $view->share('currentUser', $user);
        $view->share('gate', $this->container->get(\App\Authorization\Gate::class));

        return $next($request);
    }

    private function ipAllowed(string $ip): bool
    {
        $list = array_filter(array_map('trim', explode(',', (string) Env::get('ADMIN_IP_ALLOWLIST', ''))));
        if ($list === []) {
            return true;
        }
        foreach ($list as $cidr) {
            if (self::inCidr($ip, $cidr)) {
                return true;
            }
        }

        return false;
    }

    private static function inCidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = str_contains($cidr, '/') ? explode('/', $cidr, 2) : [$cidr, null];
        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }
        $bits = $bits === null ? strlen($ipBin) * 8 : (int) $bits;
        $bytes = intdiv($bits, 8);
        $remainder = $bits % 8;
        if (strncmp($ipBin, $subnetBin, $bytes) !== 0) {
            return false;
        }
        if ($remainder === 0) {
            return true;
        }
        $mask = chr((0xFF << (8 - $remainder)) & 0xFF);

        return (($ipBin[$bytes] & $mask) === ($subnetBin[$bytes] & $mask));
    }
}
