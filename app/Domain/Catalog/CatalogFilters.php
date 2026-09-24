<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Http\Request;

/**
 * Filtri del catalogo condivisi da elenco, mappa e API (parametri URL in italiano, stabili in tutte le lingue).
 *
 * @phpstan-type Filters array{q: string, category: ?string, territory: ?int, language: ?string, mediation: bool, free: bool, need?: ?string}
 */
final class CatalogFilters
{
    /** Modalità di accesso filtrabili nel pubblico (RF-05). */
    public const ACCESS_MODES = ['in_person', 'phone', 'online', 'home_visit'];

    /** @return array{q: string, category: ?string, territory: ?int, language: ?string, mediation: bool, free: bool, need: ?string} */
    public static function fromRequest(Request $request): array
    {
        $category = $request->string('categoria');
        $language = $request->string('lingua');
        $need = $request->string('bisogno');
        $territory = $request->int('comune');
        $orgType = $request->string('tipo_ente');
        $mode = $request->string('modalita');

        return [
            'q' => mb_substr($request->string('q'), 0, 200),
            'category' => preg_match('/^[a-z_]{2,60}$/', $category) ? $category : null,
            'territory' => $territory > 0 ? $territory : null,
            'language' => preg_match('/^[a-z]{2,3}$/', $language) ? $language : null,
            'mediation' => $request->string('mediazione') === '1',
            'free' => $request->string('gratuito') === '1',
            'need' => preg_match('/^[a-z_]{2,50}$/', $need) ? $need : null,
            'accessible' => $request->string('accessibile') === '1',
            'org_type' => preg_match('/^[a-z_]{2,50}$/', $orgType) ? $orgType : null,
            'access_mode' => in_array($mode, self::ACCESS_MODES, true) ? $mode : null,
        ];
    }

    /**
     * Parametri da conservare negli URL (paginazione, mappa ⇄ elenco).
     *
     * @param array<string, mixed> $filters
     * @return array<string, string>
     */
    public static function toQuery(array $filters): array
    {
        return array_filter([
            'q' => (string) ($filters['q'] ?? ''),
            'bisogno' => (string) ($filters['need'] ?? ''),
            'categoria' => (string) ($filters['category'] ?? ''),
            'comune' => !empty($filters['territory']) ? (string) $filters['territory'] : '',
            'lingua' => (string) ($filters['language'] ?? ''),
            'mediazione' => !empty($filters['mediation']) ? '1' : '',
            'gratuito' => !empty($filters['free']) ? '1' : '',
            'accessibile' => !empty($filters['accessible']) ? '1' : '',
            'tipo_ente' => (string) ($filters['org_type'] ?? ''),
            'modalita' => (string) ($filters['access_mode'] ?? ''),
        ], static fn (string $v): bool => $v !== '');
    }
}
