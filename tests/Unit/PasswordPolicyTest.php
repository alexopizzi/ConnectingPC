<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Security\PasswordPolicy;
use PHPUnit\Framework\TestCase;

final class PasswordPolicyTest extends TestCase
{
    private PasswordPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new PasswordPolicy(APP_BASE_PATH . '/config/common-passwords.txt');
    }

    public function testAcceptsLongPassphrase(): void
    {
        self::assertSame([], $this->policy->validate('una frase lunga e sicura', 'una frase lunga e sicura', 'mario@example.org'));
    }

    public function testRejectsShortCommonMismatchedAndEmailBased(): void
    {
        self::assertContains('auth.password.too_short', $this->policy->validate('corta', 'corta'));
        self::assertContains('auth.password.common', $this->policy->validate('Password1234', 'Password1234'));
        self::assertContains('auth.password.mismatch', $this->policy->validate('una frase lunga e sicura', 'altra frase lunga'));
        self::assertContains('auth.password.contains_email', $this->policy->validate('mario.rossi-2026!', 'mario.rossi-2026!', 'mario.rossi@example.org'));
    }
}
