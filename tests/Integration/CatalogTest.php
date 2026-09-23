<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Database\Seeder;
use App\Domain\Catalog\CatalogRepository;
use App\Domain\Search\TextNormalizer;

/**
 * Catalogo pubblico sui dati dimostrativi: ricerca per bisogno e sinonimi, ripiego delle traduzioni,
 * visibilità dei soli recapiti pubblici.
 */
final class CatalogTest extends DatabaseTestCase
{
    private static bool $demoLoaded = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (!self::$demoLoaded) {
            // I seed dimostrativi vanno fuori dalla transazione del test: si caricano una volta sola.
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

    private function firstName(array $ids, string $locale): string
    {
        return $this->catalog()->serviceSummaries([$ids[0]], $locale)[0]['name']['text'];
    }

    public function testSearchWithEverydayWordsFindsResidencePermitFirst(): void
    {
        $ids = $this->catalog()->searchServiceIds(['q' => 'devo rinnovare il permesso'], 'it');
        self::assertNotEmpty($ids);
        self::assertStringContainsString('rinnovo del permesso', $this->firstName($ids, 'it'));

        $arabic = $this->catalog()->searchServiceIds(['q' => 'تجديد الإقامة'], 'ar');
        self::assertSame($ids[0], $arabic[0]);

        $french = $this->catalog()->searchServiceIds(['q' => 'titre de séjour'], 'fr');
        self::assertContains($ids[0], $french);
    }

    public function testNeedAndLanguageFilters(): void
    {
        $health = $this->catalog()->searchServiceIds(['need' => 'health'], 'it');
        self::assertGreaterThanOrEqual(2, count($health));

        $arabicWithMediator = $this->catalog()->searchServiceIds(['language' => 'ar', 'mediation' => true], 'it');
        foreach ($this->catalog()->serviceSummaries($arabicWithMediator, 'it') as $service) {
            self::assertContains($service['mediation'], ['available', 'on_request']);
        }
        self::assertSame([], $this->catalog()->searchServiceIds(['q' => 'xyzabc'], 'it'));
    }

    public function testMissingTranslationFallsBackToItalianAndIsMarked(): void
    {
        $id = $this->catalog()->searchServiceIds(['q' => 'rinnovo permesso'], 'it')[0];
        $service = $this->catalog()->service($id, 'ar');

        self::assertFalse($service['texts']['name']['fallback']);
        self::assertSame('ar', $service['texts']['name']['lang']);
        self::assertTrue($service['texts']['documents']['fallback']);
        self::assertSame('it', $service['texts']['documents']['lang']);
    }

    public function testOnlyPublicContactsAndNoAddressForHiddenSites(): void
    {
        $orgId = (int) $this->db->fetchValue("SELECT id FROM organizations WHERE name = 'Associazione Esempio Ponte'");
        $organization = $this->catalog()->organization($orgId, 'it');
        foreach ($organization['contacts'] as $contact) {
            self::assertStringNotContainsString('referente.', $contact['value'], 'Il recapito del referente è riservato agli amministratori');
        }

        $antiviolenceId = (int) $this->db->fetchValue("SELECT id FROM organizations WHERE name = 'Centro Antiviolenza Esempio'");
        $site = $this->catalog()->organization($antiviolenceId, 'it')['sites'][0];
        self::assertNull($site['address_line']);
        self::assertNull($site['lat']);
    }

    public function testNormalizerHandlesAccentsAndArabicVariants(): void
    {
        self::assertSame('titre de sejour', TextNormalizer::normalize('Titre de SÉJOUR!'));
        self::assertSame(TextNormalizer::normalize('الإقامة'), TextNormalizer::normalize('الاقامه'));
    }
}
