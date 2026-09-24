<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Database\Seeder;
use App\Domain\Communities\CommunityRepository;
use App\Domain\Mediators\MediatorRepository;

/**
 * Directory "Associazioni e comunità" e mediatori sui dati dimostrativi: ricerca, filtri e soprattutto
 * le regole di visibilità dei dati personali dei mediatori (vault "63", D-013, D-033).
 */
final class CommunitiesMediatorsTest extends DatabaseTestCase
{
    private const NO_FILTERS = ['language' => null, 'type' => null, 'domain' => null, 'territory' => null, 'available' => false];

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

    private function mediators(): MediatorRepository
    {
        return $this->container->get(MediatorRepository::class);
    }

    private function communities(): CommunityRepository
    {
        return $this->container->get(CommunityRepository::class);
    }

    /** @param array<string, mixed> $filters */
    private function orgNames(array $filters): array
    {
        $base = ['q' => '', 'language' => null, 'community' => null, 'country' => null, 'territory' => null, 'type' => null, 'community_based' => false];
        $ids = $this->communities()->searchOrganizationIds([...$base, ...$filters], 'it');

        return array_column($this->communities()->summaries($ids, 'it'), 'name');
    }

    public function testPublicProfilesRequireVisibilityAndConsent(): void
    {
        $profiles = $this->mediators()->publicProfiles(self::NO_FILTERS, 'it');
        $names = array_column($profiles, 'display_name');

        self::assertContains('Amina K.', $names);
        self::assertNotContains('Aissatou B.', $names, 'profilo "pubblico" senza consenso');
        self::assertNotContains('Fatima B.', $names, 'profilo solo operatori');
        self::assertNotContains('Karim H.', $names, 'profilo solo admin');

        foreach ($profiles as $profile) {
            self::assertNull($profile['full_name']);
            self::assertArrayNotHasKey('qualifications_admin', $profile);
            foreach ($profile['contacts'] as $contact) {
                self::assertNotSame('mobile', $contact['kind'], 'il cellulare è solo per operatori');
            }
        }
    }

    public function testAggregatedGroupsCountOnlyNonPublicProfilesWithoutPersonalData(): void
    {
        $groups = array_column($this->mediators()->aggregatedByOrganization(self::NO_FILTERS, 'it'), null, 'organization_name');

        // Rete Mediatori Esempio: Fatima, Youssef, Tesfay (Amina è pubblica, Karim è solo admin)
        self::assertSame(3, $groups['Rete Mediatori Esempio']['count']);
        // Teranga: Aissatou ("pubblica" senza consenso), non Moussa (pubblico con consenso)
        self::assertSame(1, $groups['Associazione Esempio Teranga']['count']);
        self::assertSame(
            ['organization_id', 'organization_name', 'count', 'languages', 'domains', 'contacts'],
            array_keys($groups['Rete Mediatori Esempio']),
        );
    }

    public function testOperatorsSeeFullNamesButNeverAdminProfiles(): void
    {
        $profiles = array_column($this->mediators()->operatorProfiles(self::NO_FILTERS, 'it'), null, 'display_name');

        self::assertArrayHasKey('Fatima B.', $profiles);
        self::assertArrayHasKey('Aissatou B.', $profiles);
        self::assertArrayNotHasKey('Karim H.', $profiles);
        self::assertArrayNotHasKey('Samira N.', $profiles);
        self::assertSame('Fatima Esempio-Benali', $profiles['Fatima B.']['full_name']);
        self::assertContains('mobile', array_column($profiles['Fatima B.']['contacts'], 'kind'));
    }

    public function testMediatorTerritoryAndLanguageFilters(): void
    {
        $piacenza = (int) $this->db->fetchValue("SELECT id FROM territories WHERE type = 'municipality' AND name = 'Piacenza'");
        $names = array_column($this->mediators()->publicProfiles([...self::NO_FILTERS, 'territory' => $piacenza], 'it'), 'display_name');
        self::assertContains('Amina K.', $names, 'opera nel distretto della città');
        self::assertContains('Olena K.', $names, 'opera in tutta la provincia');
        self::assertNotContains('Arben H.', $names, 'opera solo nel distretto di Ponente');

        $arabic = array_column($this->mediators()->publicProfiles([...self::NO_FILTERS, 'language' => 'ar', 'available' => true], 'it'), 'display_name');
        self::assertSame(['Amina K.'], $arabic);
    }

    public function testDirectorySearchByCommunityCountryAndLanguage(): void
    {
        $senegalese = $this->orgNames(['q' => 'comunità senegalese']);
        sort($senegalese);
        self::assertSame(['Associazione Esempio Ponte', 'Associazione Esempio Teranga'], $senegalese);

        self::assertContains('Associazione Esempio Teranga', $this->orgNames(['country' => 'SN', 'community_based' => true]));
        self::assertSame('Associazione Esempio Kalyna', $this->orgNames(['q' => 'associazioni ucraine'])[0]);
        self::assertSame('Associazione Esempio Al Wafa', $this->orgNames(['q' => 'arabo'])[0]);
        self::assertContains('Associazione Esempio Ponti di Culture', $this->orgNames(['community' => 'intercultural']));
    }

    public function testOrganizationProfileShowsCommunities(): void
    {
        $id = (int) $this->db->fetchValue("SELECT id FROM organizations WHERE name = 'Associazione Esempio Shqiponja'");
        $profile = $this->communities()->profileOf($id, 'en');

        self::assertSame(['albanian'], array_column($profile['communities'], 'code'));
        self::assertSame('Albanian community', $profile['communities'][0]['name']['text']);
        self::assertSame(['AL', 'XK'], $profile['countries']);
    }
}
