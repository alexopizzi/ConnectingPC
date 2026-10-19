<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Database\Installer;
use App\Domain\Users\SuperAdminCreator;
use DomainException;

/**
 * Installazione senza SSH (D-041): primo super amministratore con link di attivazione,
 * stato delle migrazioni letto dall'installer.
 */
final class InstallationTest extends DatabaseTestCase
{
    public function testSuperAdminIsCreatedOnceWithAnActivationLink(): void
    {
        $creator = $this->container->get(SuperAdminCreator::class);
        $before = $creator->exists();

        $link = $creator->create('Nuovo.Admin@Example.org', 'Nuovo Admin', 'it', 'test');
        self::assertMatchesRegularExpression('#/it/invito\?token=[A-Za-z0-9_-]{20,}#', $link);
        self::assertTrue($creator->exists());
        self::assertSame('invited', $this->db->fetchValue("SELECT status FROM users WHERE email = 'nuovo.admin@example.org'"));
        self::assertSame(1, (int) $this->db->fetchValue(
            "SELECT COUNT(*) FROM role_assignments ra JOIN roles r ON r.id = ra.role_id JOIN users u ON u.id = ra.user_id
              WHERE u.email = 'nuovo.admin@example.org' AND r.code = 'super_admin' AND ra.scope_type = 'global'"
        ));
        self::assertTrue($before || $creator->exists());

        $this->expectException(DomainException::class);
        $creator->create('nuovo.admin@example.org', 'Doppione', 'it', 'test');
    }

    public function testInvalidDataIsRejected(): void
    {
        $this->expectException(DomainException::class);
        $this->container->get(SuperAdminCreator::class)->create('non-una-email', 'Nome', 'it', 'test');
    }

    public function testMigrationStatusListsEveryMigrationAsApplied(): void
    {
        $status = $this->container->get(Installer::class)->migrationStatus();
        self::assertNotEmpty($status);
        foreach ($status as $row) {
            self::assertNotNull($row['applied_at'], $row['version']);
            self::assertTrue($row['checksum_ok'], $row['version']);
        }
    }
}
