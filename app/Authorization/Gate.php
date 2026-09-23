<?php

declare(strict_types=1);

namespace App\Authorization;

use App\Http\HttpException;

/**
 * Unico punto decisionale dell'autorizzazione (D-008, vault "51 - Ruoli e permessi").
 * Deny by default. Da invocare sempre lato server; nei template serve solo a nascondere comandi.
 */
final class Gate
{
    /**
     * @param list<string> $organizationEditPermissions permessi che richiedono portal_edit_enabled
     * @param list<string> $organizationPublishPermissions permessi che richiedono la politica "direct"
     */
    public function __construct(
        private readonly AuthorizationData $data,
        private readonly string $defaultPublicationPolicy,
        private readonly array $organizationEditPermissions,
        private readonly array $organizationPublishPermissions,
    ) {
    }

    /**
     * @param array{id: int|string, status: string}|null $user
     */
    public function allows(?array $user, string $permission, ?ResourceScope $resource = null): bool
    {
        if ($user === null || ($user['status'] ?? null) !== 'active') {
            return false;
        }

        foreach ($this->data->assignments((int) $user['id']) as $assignment) {
            if (!in_array($permission, $assignment['permissions'], true)) {
                continue;
            }
            if ($this->scopeMatches($assignment, $permission, $resource)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array{id: int|string, status: string}|null $user
     * @throws HttpException 403
     */
    public function authorize(?array $user, string $permission, ?ResourceScope $resource = null): void
    {
        if (!$this->allows($user, $permission, $resource)) {
            throw new HttpException(403);
        }
    }

    /**
     * Organizzazioni in cui l'utente ha il permesso (per filtrare elenchi e menu).
     *
     * @param array{id: int|string, status: string}|null $user
     * @return list<int>
     */
    public function organizationsWith(?array $user, string $permission): array
    {
        if ($user === null || ($user['status'] ?? null) !== 'active') {
            return [];
        }
        $ids = [];
        foreach ($this->data->assignments((int) $user['id']) as $assignment) {
            if ($assignment['scope_type'] === 'organization'
                && in_array($permission, $assignment['permissions'], true)
                && $this->organizationAllows((int) $assignment['scope_key'], $permission)) {
                $ids[] = (int) $assignment['scope_key'];
            }
        }

        return array_values(array_unique($ids));
    }

    public function effectivePublicationPolicy(int $organizationId): string
    {
        return $this->data->organization($organizationId)['publication_policy'] ?? $this->defaultPublicationPolicy;
    }

    /**
     * @param array{scope_type: string, scope_key: string} $assignment
     */
    private function scopeMatches(array $assignment, string $permission, ?ResourceScope $resource): bool
    {
        $key = $assignment['scope_key'];

        return match ($assignment['scope_type']) {
            'global' => true,
            // Senza risorsa: "in almeno una delle mie organizzazioni" (es. accesso all'area riservata).
            'organization' => ($resource === null || $resource->organizationId === (int) $key)
                && $this->organizationAllows((int) $key, $permission),
            'category' => $resource !== null && in_array((int) $key, $resource->categoryIds, true),
            'territory' => $resource !== null && in_array((int) $key, $resource->territoryIds, true),
            'locale' => $resource !== null && $resource->locale === $key,
            default => false,
        };
    }

    private function organizationAllows(int $organizationId, string $permission): bool
    {
        $organization = $this->data->organization($organizationId);
        if ($organization === null || $organization['access_status'] !== 'enabled') {
            return false;
        }
        if (in_array($permission, $this->organizationEditPermissions, true) && !$organization['portal_edit_enabled']) {
            return false;
        }
        if (in_array($permission, $this->organizationPublishPermissions, true)
            && ($organization['publication_policy'] ?? $this->defaultPublicationPolicy) !== 'direct') {
            return false;
        }

        return true;
    }
}
