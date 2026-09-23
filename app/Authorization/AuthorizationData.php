<?php

declare(strict_types=1);

namespace App\Authorization;

/** Fonte dei dati per il Gate (database in produzione, dati in memoria nei test). */
interface AuthorizationData
{
    /**
     * Assegnazioni valide (non revocate, non scadute) dell'utente con i permessi del ruolo.
     *
     * @return list<array{assignment_id: int, role: string, scope_type: string, scope_key: string, permissions: list<string>}>
     */
    public function assignments(int $userId): array;

    /**
     * @return array{access_status: string, portal_edit_enabled: bool, publication_policy: ?string}|null
     */
    public function organization(int $organizationId): ?array;
}
