<?php

declare(strict_types=1);

namespace App\Authorization;

/**
 * Ambito della risorsa su cui si chiede un permesso: organizzazione proprietaria, categorie e territori
 * (già espansi con gli antenati dal chiamante), lingua della traduzione.
 */
final class ResourceScope
{
    /**
     * @param list<int> $categoryIds
     * @param list<int> $territoryIds
     */
    public function __construct(
        public readonly ?int $organizationId = null,
        public readonly array $categoryIds = [],
        public readonly array $territoryIds = [],
        public readonly ?string $locale = null,
    ) {
    }

    public static function organization(int $organizationId): self
    {
        return new self(organizationId: $organizationId);
    }
}
