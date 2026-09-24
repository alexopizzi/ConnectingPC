<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Audit\AuditLogger;
use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;

/**
 * Esportazioni CSV per le verifiche (RF-42, permesso organizations.view_all sulla rotta).
 * Solo dati di catalogo e recapiti pubblici: niente dati personali riservati. Ogni esportazione va nell'audit.
 */
final class ExportController extends Controller
{
    public function services(Request $request): Response
    {
        $rows = $this->db()->fetchAll(
            "SELECT s.id, COALESCE(t.name, '') AS name, o.name AS organization, ct.name AS category, s.publication_status,
                    s.cost_type, s.mediation, s.access_modes, s.next_review_at, s.verified_at,
                    (SELECT GROUP_CONCAT(DISTINCT tr.name ORDER BY tr.name SEPARATOR ', ') FROM service_sites ss JOIN sites si ON si.id = ss.site_id
                       JOIN territories tr ON tr.id = si.territory_id WHERE ss.service_id = s.id) AS towns,
                    (SELECT GROUP_CONCAT(CONCAT(x.locale, ':', x.status) ORDER BY x.locale SEPARATOR ' ') FROM service_translations x WHERE x.service_id = s.id) AS translations
               FROM services s JOIN organizations o ON o.id = s.organization_id
               LEFT JOIN service_translations t ON t.service_id = s.id AND t.locale = s.source_locale
               LEFT JOIN category_translations ct ON ct.category_id = s.primary_category_id AND ct.locale = 'it'
              ORDER BY o.name, name"
        );

        return $this->csv('servizi', ['id', 'servizio', 'organizzazione', 'categoria', 'pubblicazione', 'costo', 'mediazione', 'accesso', 'prossima_revisione', 'verificato_il', 'comuni', 'traduzioni'], $rows);
    }

    public function organizations(Request $request): Response
    {
        $rows = $this->db()->fetchAll(
            "SELECT o.id, o.name, tt.name AS type, o.is_community_based, o.census_status, o.listing_status, o.verification_status,
                    o.access_status, o.publication_status, o.next_review_at,
                    (SELECT GROUP_CONCAT(c.value ORDER BY c.sort_order SEPARATOR ' | ') FROM contact_points c
                      WHERE c.owner_type = 'organization' AND c.owner_id = o.id AND c.visibility = 'public') AS public_contacts,
                    (SELECT COUNT(*) FROM services s WHERE s.organization_id = o.id) AS services
               FROM organizations o
               LEFT JOIN organization_type_translations tt ON tt.organization_type_id = o.organization_type_id AND tt.locale = 'it'
              ORDER BY o.name"
        );

        return $this->csv('organizzazioni', ['id', 'nome', 'tipo', 'di_comunita', 'censimento', 'elenco', 'verifica', 'accesso', 'pubblicazione', 'prossima_revisione', 'recapiti_pubblici', 'servizi'], $rows);
    }

    /**
     * CSV UTF-8 con BOM (si apre correttamente in Excel), separatore ";" come nelle impostazioni italiane.
     * Le celle che iniziano con = + - @ sono protette dall'interpretazione come formule.
     *
     * @param list<string> $header
     * @param list<array<string, mixed>> $rows
     */
    private function csv(string $name, array $header, array $rows): Response
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $header, ';', '"', '');
        foreach ($rows as $row) {
            fputcsv($handle, array_map(static function ($value): string {
                $value = (string) $value;

                return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
            }, array_values($row)), ';', '"', '');
        }
        rewind($handle);
        $body = (string) stream_get_contents($handle);
        fclose($handle);
        $this->container->get(AuditLogger::class)->log('export.' . $name, null, null, ['rows' => count($rows)]);

        return new Response($body, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="connectingpc-' . $name . '-' . gmdate('Y-m-d') . '.csv"',
            'Cache-Control' => 'no-store',
        ]);
    }
}
