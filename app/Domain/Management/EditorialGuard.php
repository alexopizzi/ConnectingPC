<?php

declare(strict_types=1);

namespace App\Domain\Management;

use App\Authorization\Gate;
use App\Authorization\ResourceScope;
use App\Core\Database;
use DomainException;

/**
 * Regole editoriali comuni ad area amministrativa e area riservata (vault "52", "53", D-022):
 * permesso nell'ambito dell'organizzazione, stato di pubblicazione ammesso, stato delle traduzioni.
 * Tutte le decisioni passano dal Gate: i moduli non proteggono nulla.
 */
final class EditorialGuard
{
    public const PUBLICATION_STATUSES = ['draft', 'in_review', 'published', 'archived'];

    public function __construct(private readonly Gate $gate, private readonly Database $database)
    {
    }

    /**
     * @param array<string, mixed>|null $actor
     * @throws DomainException se l'utente non ha il permesso sull'organizzazione
     */
    public function authorize(?array $actor, string $permission, int $organizationId): void
    {
        if (!$this->gate->allows($actor, $permission, ResourceScope::organization($organizationId))) {
            throw new DomainException('manage.error.forbidden');
        }
    }

    /** @param array<string, mixed>|null $actor */
    public function allows(?array $actor, string $permission, ?int $organizationId = null): bool
    {
        return $this->gate->allows($actor, $permission, $organizationId === null ? null : ResourceScope::organization($organizationId));
    }

    /**
     * Un contenuto già pubblicato può essere modificato direttamente solo da chi può pubblicare.
     * Per gli altri (politica "review") la versione online deve restare invariata fino all'approvazione:
     * servono le richieste di modifica (`change_requests`, vault "53", Modello B), previste con la v0.9.
     *
     * @param array<string, mixed>|null $actor
     */
    public function assertCanChangePublished(?array $actor, int $organizationId, ?string $currentStatus): void
    {
        if ($currentStatus === 'published' && !$this->allows($actor, 'content.publish', $organizationId)) {
            throw new DomainException('manage.error.change_request_required');
        }
    }

    /**
     * Stato di pubblicazione effettivo: chi non può pubblicare direttamente manda in revisione (D-022).
     *
     * @param array<string, mixed>|null $actor
     * @return array{status: string, published_at?: string, archived_at?: ?string}
     */
    public function publication(?array $actor, int $organizationId, string $requested, ?string $current = null): array
    {
        if (!in_array($requested, self::PUBLICATION_STATUSES, true)) {
            throw new DomainException('manage.error.invalid_status');
        }
        $this->assertCanChangePublished($actor, $organizationId, $current);
        $canPublish = $this->allows($actor, 'content.publish', $organizationId);
        if (in_array($requested, ['published', 'archived'], true) && !$canPublish) {
            // Un contenuto già pubblicato resta tale solo se lo conferma chi può pubblicare.
            $requested = 'in_review';
        }
        $result = ['status' => $requested];
        if ($requested === 'published' && $current !== 'published') {
            $result['published_at'] = gmdate('Y-m-d H:i:s');
        }
        $result['archived_at'] = $requested === 'archived' ? gmdate('Y-m-d H:i:s') : null;

        return $result;
    }

    /**
     * Stato di una traduzione: il testo nella lingua sorgente è sempre approvato; le altre lingue sono
     * approvate solo se chi scrive può approvare traduzioni, altrimenti "da revisionare".
     *
     * @param array<string, mixed>|null $actor
     */
    public function translationStatus(?array $actor, int $organizationId, string $locale, string $sourceLocale, bool $approve): string
    {
        if ($locale === $sourceLocale) {
            return 'approved';
        }
        if ($approve && ($this->allows($actor, 'translations.approve', $organizationId)
            || $this->gate->allows($actor, 'translations.approve', new ResourceScope(locale: $locale)))) {
            return 'approved';
        }

        return 'to_review';
    }

    /** @return list<string> lingue dei contenuti (lingue attive dell'interfaccia) */
    public function contentLocales(): array
    {
        return array_map('strval', $this->database->fetchColumn('SELECT code FROM locales WHERE is_enabled = 1 ORDER BY sort_order'));
    }

    /** Data facoltativa AAAA-MM-GG dai moduli. */
    public static function date(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value ? $value : throw new DomainException('manage.error.invalid_date');
    }
}
