<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\FileCache;
use App\I18n\TranslationLoader;
use App\I18n\Translator;
use PHPUnit\Framework\TestCase;

final class TranslatorTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/cpc-i18n-' . bin2hex(random_bytes(4));
        mkdir($this->dir . '/lang', 0777, true);
        file_put_contents($this->dir . '/lang/it.php', '<?php return ' . var_export([
            'greeting' => 'Ciao {name}',
            'results' => '{count, plural, =0 {nessun risultato} one {# risultato} other {# risultati}}',
            'only_it' => 'Solo in italiano',
        ], true) . ';');
        file_put_contents($this->dir . '/lang/ar.php', '<?php return ' . var_export([
            'greeting' => 'مرحباً {name}',
            'results' => '{count, plural, zero {لا نتائج} one {نتيجة واحدة} two {نتيجتان} few {# نتائج} many {# نتيجة} other {# نتيجة}}',
        ], true) . ';');
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/lang/*') ?: []);
        array_map('unlink', glob($this->dir . '/cache/*') ?: []);
        @rmdir($this->dir . '/lang');
        @rmdir($this->dir . '/cache');
        @rmdir($this->dir);
    }

    private function translator(string $locale): Translator
    {
        // Nessun database: il loader usa i file.
        $loader = new TranslationLoader(null, new FileCache($this->dir . '/cache'), $this->dir . '/lang', 'it');

        return new Translator($loader, 'it', $locale);
    }

    public function testParametersAndFallbackToSourceLocale(): void
    {
        $ar = $this->translator('ar');
        self::assertSame('مرحباً Amina', $ar->get('greeting', ['name' => 'Amina']));
        self::assertSame('Solo in italiano', $ar->get('only_it'));
        self::assertSame('missing.key', $ar->get('missing.key'));
        self::assertSame(['missing.key'], $ar->missingKeys());
    }

    public function testIcuPluralRulesPerLocale(): void
    {
        $it = $this->translator('it');
        self::assertSame('nessun risultato', $it->get('results', ['count' => 0]));
        self::assertSame('1 risultato', $it->get('results', ['count' => 1]));
        self::assertSame('5 risultati', $it->get('results', ['count' => 5]));

        $ar = $this->translator('ar');
        self::assertSame('نتيجتان', $ar->get('results', ['count' => 2]));
    }
}
