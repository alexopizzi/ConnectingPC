<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    public function testAcceptedLanguagesOrderedByQuality(): void
    {
        $request = new Request('GET', '/', server: ['HTTP_ACCEPT_LANGUAGE' => 'ar-MA;q=0.7, fr-FR, en;q=0.9, *;q=0.1']);

        self::assertSame(['fr', 'en', 'ar'], $request->acceptedLanguages());
    }

    public function testStringIsTrimmedAndNormalized(): void
    {
        // "e" + accento combinante → forma composta NFC
        $request = new Request('POST', '/', body: ['name' => "  Andre\u{0301}  ", 'list' => ['x']]);

        self::assertSame("Andr\u{00E9}", $request->string('name'));
        self::assertSame('', $request->string('list'));
        self::assertTrue($request->isWrite());
    }

    public function testForwardedForIgnoredWithoutTrustedProxy(): void
    {
        $request = new Request('GET', '/', server: ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_X_FORWARDED_FOR' => '1.2.3.4']);

        self::assertSame('10.0.0.1', $request->ip());
    }
}
