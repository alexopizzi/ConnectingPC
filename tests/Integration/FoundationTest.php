<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Audit\AuditLogger;
use App\Domain\Settings\SettingsRepository;
use App\I18n\LocaleRegistry;
use App\I18n\TranslationImporter;
use App\I18n\TranslationLoader;

final class FoundationTest extends DatabaseTestCase
{
    public function testSeededLocalesIncludeArabicRtl(): void
    {
        $rows = $this->db->fetchAll('SELECT code, direction FROM locales WHERE is_enabled = 1 ORDER BY sort_order');

        self::assertSame(['it', 'en', 'fr', 'ar'], array_column($rows, 'code'));
        self::assertSame('rtl', $rows[3]['direction']);
    }

    public function testDatabaseSessionIsUtcAndStrict(): void
    {
        $row = $this->db->fetchOne('SELECT @@session.time_zone AS tz, @@session.sql_mode AS mode');

        self::assertSame('+00:00', $row['tz']);
        self::assertStringContainsString('STRICT_ALL_TABLES', $row['mode']);
    }

    public function testDefaultPublicationPolicyIsDirect(): void
    {
        $settings = $this->container->get(SettingsRepository::class);

        self::assertSame('direct', $settings->get('publication.default_policy'));
    }

    public function testImportCreatesStringsAndMarksChangedSourceAsOutdated(): void
    {
        $importer = $this->container->get(TranslationImporter::class);
        $locales = array_keys($this->container->get(LocaleRegistry::class)->enabled());
        $importer->import($locales);

        $count = (int) $this->db->fetchValue("SELECT COUNT(*) FROM ui_string_translations WHERE locale = 'ar' AND status = 'to_review'");
        self::assertGreaterThan(50, $count);

        // Cambio del testo sorgente → traduzioni "outdated"
        $this->db->execute("UPDATE ui_strings SET source_text = 'Testo modificato' WHERE `key` = 'home.title'");
        $importer->import($locales);
        $status = $this->db->fetchValue(
            "SELECT t.status FROM ui_string_translations t JOIN ui_strings s ON s.id = t.ui_string_id
              WHERE s.`key` = 'home.title' AND t.locale = 'fr'"
        );
        self::assertSame('outdated', $status);

        $this->container->get(TranslationLoader::class)->forget();
    }

    public function testAuditLogRedactsSecretsAndPseudonymisesIp(): void
    {
        $audit = $this->container->get(AuditLogger::class);
        $audit->setActor(null);
        $audit->setIp('203.0.113.9');
        $audit->log('test.action', 'user', 5, AuditLogger::diff(
            ['display_name' => 'A', 'password_hash' => 'x'],
            ['display_name' => 'B', 'password_hash' => 'y'],
        ));

        $row = $this->db->fetchOne("SELECT * FROM audit_log WHERE action = 'test.action' ORDER BY id DESC LIMIT 1");
        $changes = json_decode((string) $row['changes'], true);

        self::assertSame(['old' => 'A', 'new' => 'B'], $changes['display_name']);
        self::assertSame('[redatto]', $changes['password_hash']);
        self::assertSame(64, strlen((string) $row['ip_hash']));
        self::assertStringNotContainsString('203.0.113.9', json_encode($row));
    }
}
