<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Authorization\DatabaseAuthorizationData;
use App\Database\Seeder;
use App\Domain\Management\MediatorEditor;
use App\Domain\Management\OrganizationEditor;
use App\Domain\Management\RelatedRecords;
use App\Domain\Management\ServiceEditor;
use App\Domain\Management\SiteEditor;
use DomainException;

/**
 * Gestione dei contenuti: permessi nell'ambito dell'organizzazione, politica di pubblicazione (D-022),
 * traduzioni "da aggiornare", consenso dei mediatori (D-033), validazione di recapiti e orari.
 */
final class ManagementTest extends DatabaseTestCase
{
    private static bool $demoLoaded = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$demoLoaded) {
            $this->db->pdo()->commit();
            (new Seeder($this->container, APP_BASE_PATH . '/database/seeds'))->run(demo: true);
            self::$demoLoaded = true;
            $this->db->pdo()->beginTransaction();
        }
        $this->container->get(DatabaseAuthorizationData::class)->forget();
    }

    /** @return array<string, mixed> */
    private function user(string $email): array
    {
        return $this->db->fetchOne('SELECT * FROM users WHERE email = ?', [$email]) ?? self::fail("Utente $email assente");
    }

    private function orgId(string $name): int
    {
        return (int) $this->db->fetchValue('SELECT id FROM organizations WHERE name = ?', [$name]);
    }

    private function serviceOf(int $organizationId): int
    {
        return (int) $this->db->fetchValue('SELECT id FROM services WHERE organization_id = ? ORDER BY id LIMIT 1', [$organizationId]);
    }

    /** @return array<string, mixed> dati minimi di un servizio esistente, per update() */
    private function serviceData(int $serviceId, string $publication): array
    {
        return [
            'primary_category_id' => (int) $this->db->fetchValue('SELECT primary_category_id FROM services WHERE id = ?', [$serviceId]),
            'publication_status' => $publication,
        ];
    }

    public function testOrganizationUserEditsOnlyOwnOrganization(): void
    {
        $referent = $this->user('referente.ponte@connectingpc.test');
        $editor = $this->container->get(OrganizationEditor::class);
        $ponte = $this->orgId('Associazione Esempio Ponte');

        $editor->updateProfile($referent, $ponte, ['name' => 'Associazione Esempio Ponte', 'website' => 'https://ponte.example.org']);
        self::assertSame('https://ponte.example.org', $this->db->fetchValue('SELECT website FROM organizations WHERE id = ?', [$ponte]));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('manage.error.forbidden');
        $editor->updateProfile($referent, $this->orgId('Cooperativa Esempio Orizzonti'), ['name' => 'Tentativo']);
    }

    public function testOrganizationUserCannotChangeStatusAxes(): void
    {
        $this->expectExceptionMessage('manage.error.forbidden');
        $this->container->get(OrganizationEditor::class)->updateStatus(
            $this->user('utente.orizzonti@connectingpc.test'),
            $this->orgId('Cooperativa Esempio Orizzonti'),
            ['access_status' => 'enabled', 'publication_policy' => 'direct'],
        );
    }

    public function testReviewPolicyTurnsPublishIntoReview(): void
    {
        $orgUser = $this->user('utente.orizzonti@connectingpc.test');
        $orizzonti = $this->orgId('Cooperativa Esempio Orizzonti');
        $serviceId = $this->serviceOf($orizzonti);
        $editor = $this->container->get(ServiceEditor::class);

        // Politica predefinita "direct" (D-022): pubblica direttamente
        $editor->update($orgUser, $serviceId, $this->serviceData($serviceId, 'published'));
        self::assertSame('published', $this->db->fetchValue('SELECT publication_status FROM services WHERE id = ?', [$serviceId]));

        $this->db->update('organizations', ['publication_policy' => 'review'], ['id' => $orizzonti]);
        $this->db->update('services', ['publication_status' => 'draft'], ['id' => $serviceId]);
        $this->container->get(DatabaseAuthorizationData::class)->forget();
        $editor->update($orgUser, $serviceId, $this->serviceData($serviceId, 'published'));
        self::assertSame('in_review', $this->db->fetchValue('SELECT publication_status FROM services WHERE id = ?', [$serviceId]));

        // Con revisione, la traduzione non può essere approvata dall'organizzazione
        $editor->saveTexts($orgUser, $serviceId, 'en', ['name' => 'Test service'], true);
        self::assertSame('to_review', $this->db->fetchValue("SELECT status FROM service_translations WHERE service_id = ? AND locale = 'en'", [$serviceId]));

        // Un contenuto pubblicato non si modifica senza permesso di pubblicazione: la versione online resta
        $this->db->update('services', ['publication_status' => 'published'], ['id' => $serviceId]);
        $this->expectExceptionMessage('manage.error.change_request_required');
        $editor->update($orgUser, $serviceId, $this->serviceData($serviceId, 'published'));
    }

    public function testChangingSourceTextMarksTranslationsOutdated(): void
    {
        $admin = $this->user('admin@connectingpc.test');
        $serviceId = $this->serviceOf($this->orgId('Sportello Immigrazione Esempio'));
        $editor = $this->container->get(ServiceEditor::class);
        $before = $this->db->fetchOne("SELECT * FROM service_translations WHERE service_id = ? AND locale = 'it'", [$serviceId]);

        // Stesso testo: nessuna traduzione cambia stato
        $fields = array_intersect_key($before, array_flip(ServiceEditor::TEXT_FIELDS));
        $editor->saveTexts($admin, $serviceId, 'it', $fields, false);
        self::assertSame('approved', $this->db->fetchValue("SELECT status FROM service_translations WHERE service_id = ? AND locale = 'en'", [$serviceId]));

        $editor->saveTexts($admin, $serviceId, 'it', [...$fields, 'summary' => 'Sintesi completamente nuova.'], false);
        self::assertSame('outdated', $this->db->fetchValue("SELECT status FROM service_translations WHERE service_id = ? AND locale = 'en'", [$serviceId]));
    }

    public function testMediatorPublicProfileRequiresConsentAndRevocationHidesContacts(): void
    {
        $admin = $this->user('admin@connectingpc.test');
        $editor = $this->container->get(MediatorEditor::class);
        $data = ['first_name' => 'Prova', 'last_name' => 'Consenso', 'mediation_types' => ['linguistic'], 'profile_visibility' => 'public', 'publication_status' => 'published'];

        try {
            $editor->save($admin, null, $data);
            self::fail('Profilo pubblico senza consenso accettato');
        } catch (DomainException $e) {
            self::assertSame('manage.error.consent_required', $e->getMessage());
        }

        $id = $editor->save($admin, null, [...$data, 'consent_given' => true]);
        $editor->saveContacts($admin, $id, [['kind' => 'email', 'value' => 'prova@example.org', 'visibility' => 'public']]);

        $editor->save($admin, $id, [...$data, 'profile_visibility' => 'operators', 'consent_given' => false]);
        self::assertNull($this->db->fetchValue('SELECT public_consent_at FROM mediators WHERE id = ?', [$id]));
        self::assertSame('operators', $this->db->fetchValue("SELECT visibility FROM contact_points WHERE owner_type = 'mediator' AND owner_id = ?", [$id]));
        self::assertSame(1, (int) $this->db->fetchValue("SELECT COUNT(*) FROM audit_log WHERE action = 'mediator.consent_revoked' AND entity_id = ?", [$id]));
    }

    public function testOrganizationUserManagesOnlyOwnMediatorsWithoutAdminFields(): void
    {
        $referent = $this->user('referente.ponte@connectingpc.test');
        $ponte = $this->orgId('Associazione Esempio Ponte');
        $editor = $this->container->get(MediatorEditor::class);

        $id = $editor->save($referent, null, [
            'first_name' => 'Nuovo', 'last_name' => 'Mediatore', 'organization_id' => $ponte, 'mediation_types' => ['cultural'],
            'profile_visibility' => 'operators', 'verification_status' => 'verified', 'admin_notes' => 'non deve essere salvata',
        ]);
        $row = $this->db->fetchOne('SELECT verification_status, admin_notes FROM mediators WHERE id = ?', [$id]);
        self::assertSame('unverified', $row['verification_status']);
        self::assertNull($row['admin_notes']);

        $this->expectExceptionMessage('manage.error.forbidden');
        $editor->save($referent, $id, ['first_name' => 'Nuovo', 'last_name' => 'Mediatore', 'mediation_types' => ['cultural'],
            'organization_id' => $this->orgId('Cooperativa Esempio Orizzonti')]);
    }

    public function testSiteAndRelatedRecordsValidation(): void
    {
        $admin = $this->user('admin@connectingpc.test');
        $ponte = $this->orgId('Associazione Esempio Ponte');
        $town = (int) $this->db->fetchValue("SELECT id FROM territories WHERE type = 'municipality' AND name = 'Piacenza'");
        $sites = $this->container->get(SiteEditor::class);

        $result = $sites->save($admin, $ponte, null, ['address_line' => 'Via Prova 1', 'territory_id' => $town, 'lat' => '46.9', 'lng' => '9.7']);
        self::assertSame('manage.warning.outside_province', $result['warning']);

        foreach ([
            fn () => $sites->save($admin, $ponte, null, ['address_line' => 'Via Prova 2', 'territory_id' => $town, 'lat' => '45.05']),
            fn () => $sites->saveHours($admin, $result['id'], [['weekday' => 1, 'opens' => '12:00', 'closes' => '09:00']]),
            fn () => $this->container->get(RelatedRecords::class)->replaceContacts('site', $result['id'], [['kind' => 'website', 'value' => 'javascript:alert(1)', 'visibility' => 'public']], null),
        ] as $invalid) {
            try {
                $invalid();
                self::fail('Dato non valido accettato');
            } catch (DomainException) {
                self::addToAssertionCount(1);
            }
        }
    }
}
