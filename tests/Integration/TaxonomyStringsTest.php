<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Authorization\DatabaseAuthorizationData;
use App\Domain\Management\TaxonomyEditor;
use App\Domain\Management\UiStringEditor;
use App\Http\HttpException;
use DomainException;

/** Tassonomie (RF-31) e traduzioni delle stringhe dell'interfaccia (RF-36). */
final class TaxonomyStringsTest extends DatabaseTestCase
{
    /** @return array<string, mixed> utente fittizio con un ruolo e un ambito */
    private function actor(string $role, string $scopeType = 'global', string $scopeKey = ''): array
    {
        $id = $this->db->insert('users', ['email' => $role . '.' . uniqid() . '@example.org', 'display_name' => 'Prova ' . $role, 'status' => 'active']);
        $this->db->insert('role_assignments', [
            'user_id' => $id, 'role_id' => (int) $this->db->fetchValue('SELECT id FROM roles WHERE code = ?', [$role]),
            'scope_type' => $scopeType, 'scope_key' => $scopeKey,
        ]);
        $this->container->get(DatabaseAuthorizationData::class)->forget();

        return ['id' => $id, 'status' => 'active'];
    }

    public function testCreateCategoryUnderAreaWithLabels(): void
    {
        $editor = $this->container->get(TaxonomyEditor::class);
        $admin = $this->actor('admin');
        $area = (int) $this->db->fetchValue('SELECT id FROM categories WHERE parent_id IS NULL ORDER BY sort_order LIMIT 1');

        $id = $editor->save($admin, 'categories', null, [
            'code' => 'test_category', 'sort_order' => 5, 'parent_id' => $area, 'is_active' => '1',
            'labels' => ['it' => 'Categoria di prova', 'en' => 'Test category', 'ar' => ''],
        ]);
        $item = $editor->find('categories', $id);
        self::assertSame($area, (int) $item['parent_id']);
        self::assertSame('approved', $item['labels']['it']['status']);
        self::assertSame('to_review', $item['labels']['en']['status']);
        self::assertArrayNotHasKey('ar', $item['labels']);

        // Tre livelli non sono ammessi
        $this->expectExceptionMessage('manage.error.invalid_parent');
        $editor->save($admin, 'categories', null, ['code' => 'test_child', 'parent_id' => $id, 'labels' => ['it' => 'Figlia']]);
    }

    public function testCodeRulesAndPermission(): void
    {
        $editor = $this->container->get(TaxonomyEditor::class);
        $admin = $this->actor('admin');
        foreach (['Codice Non Valido', 'health'] as $code) {
            try {
                $editor->save($admin, 'mediation_domains', null, ['code' => $code, 'labels' => ['it' => 'X']]);
                self::fail("Codice accettato: $code");
            } catch (DomainException $e) {
                self::assertContains($e->getMessage(), ['manage.error.invalid_code', 'manage.error.code_taken']);
            }
        }

        $this->expectException(HttpException::class);
        $editor->save($this->actor('translator', 'locale', 'ar'), 'mediation_domains', null, ['code' => 'nuovo_ambito', 'labels' => ['it' => 'Nuovo']]);
    }

    public function testIcuArgumentsIgnorePluralBranches(): void
    {
        self::assertSame(['count'], UiStringEditor::arguments('{count, plural, =0 {Nessun servizio} one {# servizio} other {# servizi}}'));
        self::assertSame(['name', 'date'], UiStringEditor::arguments('Ciao {name}, oggi è {date}.'));
    }

    public function testTranslatorEditsOnlyOwnLocaleAndKeepsPlaceholders(): void
    {
        $editor = $this->container->get(UiStringEditor::class);
        $translator = $this->actor('translator', 'locale', 'ar');
        $id = $this->db->insert('ui_strings', ['key' => 'test.results', 'source_text' => '{count, plural, =0 {Nessun servizio} one {# servizio} other {# servizi}}']);

        $editor->save($translator, $id, 'ar', '{count, plural, zero {لا شيء} one {خدمة واحدة} other {# خدمة}}', true);
        self::assertSame('approved', $this->db->fetchValue("SELECT status FROM ui_string_translations WHERE ui_string_id = ? AND locale = 'ar'", [$id]));

        foreach ([['ar', 'نص بدون متغير'], ['fr', '{count} services']] as [$locale, $text]) {
            try {
                $editor->save($translator, $id, $locale, $text, false);
                self::fail("Salvataggio accettato: $locale");
            } catch (DomainException $e) {
                self::assertContains($e->getMessage(), ['manage.error.invalid_placeholders', 'manage.error.forbidden']);
            }
        }
    }
}
