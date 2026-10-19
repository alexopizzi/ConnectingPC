<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Database\Seeder;
use App\Domain\Catalog\CatalogRepository;
use App\Domain\Geo\MunicipalityShapes;

/**
 * Mappa dei comuni (D-038): confini coerenti con i territori, filtro "comune" che comprende il territorio
 * servito dichiarato, conteggi per comune usati per colorare la mappa.
 */
final class MunicipalityMapTest extends DatabaseTestCase
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
    }

    private function catalog(): CatalogRepository
    {
        return $this->container->get(CatalogRepository::class);
    }

    /** @return array{id: int, district_id: int} un comune senza sedi pubblicate */
    private function townWithoutSites(): array
    {
        $row = $this->db->fetchOne(
            "SELECT t.id, t.parent_id FROM territories t
              WHERE t.type = 'municipality' AND NOT EXISTS (SELECT 1 FROM sites si WHERE si.territory_id = t.id)
              ORDER BY t.name LIMIT 1"
        );
        self::assertNotNull($row, 'Serve almeno un comune senza sedi nei dati dimostrativi');

        return ['id' => (int) $row['id'], 'district_id' => (int) $row['parent_id']];
    }

    public function testEveryBoundaryMatchesAMunicipalityOfTheProvince(): void
    {
        $directory = $this->catalog()->municipalityDirectory();
        self::assertCount(46, $directory);

        $shapes = $this->container->get(MunicipalityShapes::class)->svg();
        self::assertCount(46, $shapes['towns']);
        self::assertGreaterThan(0, $shapes['height']);
        self::assertSame([], array_diff(array_keys($shapes['towns']), array_column($directory, 'istat')));
        foreach ($shapes['towns'] as $town) {
            self::assertMatchesRegularExpression('/^M[0-9. L]+Z/', $town['d']);
        }

        $groups = $this->catalog()->municipalityGroups();
        self::assertCount(3, $groups);
        self::assertSame(46, array_sum(array_map(static fn (array $g): int => count($g['municipalities']), $groups)));
    }

    public function testTownFilterIncludesServicesThatDeclareTheTownDistrictOrProvince(): void
    {
        $town = $this->townWithoutSites();
        $serviceId = (int) $this->db->fetchValue("SELECT id FROM services WHERE publication_status = 'published' ORDER BY id LIMIT 1");
        $before = $this->catalog()->searchServiceIds(['territory' => $town['id']], 'it');
        self::assertNotContains($serviceId, $before);

        // Servizio dichiarato per tutto il distretto del comune: è raggiungibile anche da lì
        $this->db->insert('service_territories', ['service_id' => $serviceId, 'territory_id' => $town['district_id']]);
        self::assertContains($serviceId, $this->catalog()->searchServiceIds(['territory' => $town['id']], 'it'));
        self::assertContains($serviceId, $this->catalog()->searchServiceIds(['territory' => $town['district_id']], 'it'));

        $counts = $this->catalog()->municipalityCounts([$serviceId]);
        self::assertSame(1, $counts[$town['id']] ?? 0);
        $districtTowns = $this->db->fetchColumn('SELECT id FROM territories WHERE parent_id = ?', [$town['district_id']]);
        foreach ($districtTowns as $id) {
            self::assertSame(1, $counts[(int) $id] ?? 0);
        }
        $otherTown = (int) $this->db->fetchValue("SELECT id FROM territories WHERE type = 'municipality' AND parent_id <> ? LIMIT 1", [$town['district_id']]);
        $siteTowns = $this->db->fetchColumn('SELECT si.territory_id FROM service_sites ss JOIN sites si ON si.id = ss.site_id WHERE ss.service_id = ?', [$serviceId]);
        if (!in_array($otherTown, array_map('intval', $siteTowns), true)) {
            self::assertArrayNotHasKey($otherTown, $counts);
        }
    }

    public function testProvinceWideServiceReachesEveryTown(): void
    {
        $serviceId = (int) $this->db->fetchValue("SELECT id FROM services WHERE publication_status = 'published' ORDER BY id DESC LIMIT 1");
        $province = (int) $this->db->fetchValue("SELECT id FROM territories WHERE type = 'province'");
        $this->db->insert('service_territories', ['service_id' => $serviceId, 'territory_id' => $province]);

        $counts = $this->catalog()->municipalityCounts([$serviceId]);
        self::assertCount(46, $counts);
        self::assertContains($serviceId, $this->catalog()->searchServiceIds(['territory' => $this->townWithoutSites()['id']], 'it'));
    }

    public function testCountsFollowTheOtherFilters(): void
    {
        $all = $this->catalog()->municipalityCounts($this->catalog()->searchServiceIds([], 'it'));
        $free = $this->catalog()->municipalityCounts($this->catalog()->searchServiceIds(['free' => true], 'it'));
        self::assertNotEmpty($all);
        foreach ($free as $id => $count) {
            self::assertLessThanOrEqual($all[$id], $count);
        }
        self::assertSame([], $this->catalog()->municipalityCounts([]));
    }
}
