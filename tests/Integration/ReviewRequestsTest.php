<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Authorization\DatabaseAuthorizationData;
use App\Database\Seeder;
use App\Domain\Catalog\CatalogRepository;
use App\Domain\Management\InboundRequestService;
use App\Domain\Management\ManagementRepository;
use App\Domain\Management\ReviewService;
use App\Http\HttpException;
use DomainException;

/**
 * Coda di revisione (RF-35, RF-25), registro delle richieste (RF-37), cruscotto dell'area riservata (RF-21)
 * e filtri pubblici aggiuntivi (RF-05).
 */
final class ReviewRequestsTest extends DatabaseTestCase
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

    private function ponteService(): int
    {
        return (int) $this->db->fetchValue(
            "SELECT s.id FROM services s JOIN organizations o ON o.id = s.organization_id WHERE o.name = 'Associazione Esempio Ponte' ORDER BY s.id LIMIT 1"
        );
    }

    public function testRejectWithNoteIsVisibleToOrganizationAndApprovePublishes(): void
    {
        $admin = $this->user('admin@connectingpc.test');
        $reviews = $this->container->get(ReviewService::class);
        $serviceId = $this->ponteService();
        $ponte = (int) $this->db->fetchValue('SELECT organization_id FROM services WHERE id = ?', [$serviceId]);
        $this->db->update('services', ['publication_status' => 'in_review'], ['id' => $serviceId]);

        self::assertContains($serviceId, array_column(array_filter($reviews->pendingContent(), static fn (array $r): bool => $r['entity_type'] === 'service'), 'entity_id'));

        try {
            $reviews->decide($admin, 'service', $serviceId, 'rejected', '');
            self::fail('Rifiuto senza nota accettato');
        } catch (DomainException $e) {
            self::assertSame('manage.error.note_required', $e->getMessage());
        }
        $reviews->decide($admin, 'service', $serviceId, 'rejected', 'Manca l’orario del sabato.');

        $work = $this->container->get(ManagementRepository::class)->pendingWork([$ponte]);
        $item = array_values(array_filter($work, static fn (array $w): bool => $w['entity_type'] === 'service' && $w['entity_id'] === $serviceId))[0];
        self::assertSame('rejected', $item['status']);
        self::assertSame('Manca l’orario del sabato.', $item['note']);

        $this->db->update('services', ['publication_status' => 'in_review'], ['id' => $serviceId]);
        $reviews->decide($admin, 'service', $serviceId, 'approved', '');
        self::assertSame('published', $this->db->fetchValue('SELECT publication_status FROM services WHERE id = ?', [$serviceId]));
    }

    public function testOrganizationUsersCannotReview(): void
    {
        $serviceId = $this->ponteService();
        $this->db->update('services', ['publication_status' => 'in_review'], ['id' => $serviceId]);

        $this->expectExceptionMessage('manage.error.forbidden');
        $this->container->get(ReviewService::class)->decide($this->user('referente.ponte@connectingpc.test'), 'service', $serviceId, 'approved', '');
    }

    public function testTranslationApproval(): void
    {
        $serviceId = $this->ponteService();
        $this->db->execute("UPDATE service_translations SET status = 'to_review' WHERE service_id = ? AND locale = 'fr'", [$serviceId]);
        $this->container->get(ReviewService::class)->approveTranslation($this->user('admin@connectingpc.test'), 'service', $serviceId, 'fr');

        self::assertSame('approved', $this->db->fetchValue("SELECT status FROM service_translations WHERE service_id = ? AND locale = 'fr'", [$serviceId]));
    }

    public function testInboundRequestsLifecycleAndPurge(): void
    {
        $admin = $this->user('admin@connectingpc.test');
        $requests = $this->container->get(InboundRequestService::class);
        $id = $requests->create($admin, ['type' => 'census', 'channel' => 'email', 'requester_name' => 'Mario Esempio', 'requester_email' => 'mario@example.org', 'message' => 'Vorremmo comparire sul sito.']);

        self::assertContains($id, array_map('intval', array_column($requests->list(['status' => 'open']), 'id')));
        $requests->update($admin, $id, ['type' => 'census', 'channel' => 'email', 'requester_name' => 'Mario Esempio', 'requester_email' => 'mario@example.org', 'status' => 'done', 'resolution_note' => 'Censita.']);

        $this->db->update('inbound_requests', ['retention_until' => '2020-01-01'], ['id' => $id]);
        self::assertGreaterThanOrEqual(1, $requests->purgeExpired());
        $row = $this->db->fetchOne('SELECT requester_name, requester_email, message FROM inbound_requests WHERE id = ?', [$id]);
        self::assertNull($row['requester_name']);
        self::assertNull($row['requester_email']);

        $this->expectException(HttpException::class);
        $requests->create($this->user('referente.ponte@connectingpc.test'), ['type' => 'other', 'channel' => 'email']);
    }

    public function testNewPublicFilters(): void
    {
        $catalog = $this->container->get(CatalogRepository::class);
        $all = count($catalog->searchServiceIds([], 'it'));
        $accessible = $catalog->searchServiceIds(['accessible' => true], 'it');
        $online = $catalog->searchServiceIds(['access_mode' => 'online'], 'it');
        $health = $catalog->searchServiceIds(['org_type' => 'health_authority'], 'it');

        foreach ([$accessible, $online, $health] as $subset) {
            self::assertNotEmpty($subset);
            self::assertLessThan($all, count($subset));
        }
        foreach ($catalog->serviceSummaries($health, 'it') as $service) {
            self::assertSame('health_authority', $this->db->fetchValue(
                'SELECT t.code FROM services s JOIN organizations o ON o.id = s.organization_id JOIN organization_types t ON t.id = o.organization_type_id WHERE s.id = ?',
                [$service['id']],
            ));
        }
    }
}
