<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\HttpException;
use App\Http\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
        $this->router->get('/', static fn () => null)->name('root');
        $this->router->group(['prefix' => '/{locale}', 'middleware' => ['locale'], 'area' => 'public'], function (Router $r): void {
            $r->get('', static fn () => null)->name('home');
            $r->get('/servizi/{id}', static fn () => null)->name('service');
            $r->post('/accedi', static fn () => null)->name('login');
        });
    }

    public function testMatchesRootAndLocalizedRoutes(): void
    {
        self::assertSame('root', $this->router->match('GET', '/')['route']->name);

        $match = $this->router->match('GET', '/ar');
        self::assertSame('home', $match['route']->name);
        self::assertSame(['locale' => 'ar'], $match['params']);
        self::assertSame(['locale'], $match['route']->middleware);
        self::assertSame('public', $match['route']->area);
    }

    public function testNumericIdConstraint(): void
    {
        self::assertSame(['locale' => 'it', 'id' => '42'], $this->router->match('GET', '/it/servizi/42')['params']);
        self::assertNull($this->router->match('GET', '/it/servizi/abc'));
        self::assertNull($this->router->match('GET', '/it/servizi/0'));
    }

    public function testInlineRegexWithQuantifierBraces(): void
    {
        $router = new Router();
        $router->get('/{locale}/bisogni/{code:[a-z_]{2,50}}', static fn () => null)->name('need');
        $router->get('/x/{id}/r/{assignment:[1-9][0-9]{0,9}}', static fn () => null)->name('revoke');

        self::assertSame(['locale' => 'it', 'code' => 'documents'], $router->match('GET', '/it/bisogni/documents')['params']);
        self::assertNull($router->match('GET', '/it/bisogni/x'));
        self::assertSame('/x/3/r/12', $router->path('revoke', ['id' => 3, 'assignment' => 12]));
        self::assertSame(['id' => '3', 'assignment' => '12'], $router->match('GET', '/x/3/r/12')['params']);
    }

    public function testMethodNotAllowed(): void
    {
        try {
            $this->router->match('GET', '/it/accedi');
            self::fail('Attesa eccezione 405');
        } catch (HttpException $e) {
            self::assertSame(405, $e->status);
            self::assertSame('POST', $e->headers['Allow']);
        }
    }

    public function testPathGenerationWithExtraQueryParameters(): void
    {
        self::assertSame('/fr/servizi/7?tab=orari', $this->router->path('service', ['locale' => 'fr', 'id' => 7, 'tab' => 'orari']));
        self::assertSame(['locale', 'id'], $this->router->parameters('service'));
    }
}
