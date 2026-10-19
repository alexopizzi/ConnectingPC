<?php

declare(strict_types=1);

namespace App\Domain\Geo;

/**
 * Confini dei comuni della provincia (public/assets/data/comuni-piacenza.json, ISTAT 2026 CC-BY via openpolis,
 * D-038) proiettati in coordinate SVG per la mappa statica della pagina iniziale.
 * Proiezione equirettangolare con correzione della longitudine alla latitudine media: adeguata a una provincia.
 */
final class MunicipalityShapes
{
    private const WIDTH = 600.0;

    /** @var array{width: float, height: float, towns: array<string, array{name: string, d: string, cx: float, cy: float}>}|null */
    private ?array $projected = null;

    public function __construct(private readonly string $file)
    {
    }

    /**
     * @return array{width: float, height: float, towns: array<string, array{name: string, d: string, cx: float, cy: float}>}
     *         comuni indicizzati per codice ISTAT
     */
    public function svg(): array
    {
        return $this->projected ??= $this->project();
    }

    /** @return array{width: float, height: float, towns: array<string, array{name: string, d: string, cx: float, cy: float}>} */
    private function project(): array
    {
        $data = is_file($this->file) ? json_decode((string) file_get_contents($this->file), true) : null;
        if (!is_array($data) || !isset($data['features']) || !is_array($data['features'])) {
            return ['width' => self::WIDTH, 'height' => 0.0, 'towns' => []];
        }

        $polygons = [];
        $minLng = $minLat = INF;
        $maxLng = $maxLat = -INF;
        foreach ($data['features'] as $feature) {
            $geometry = $feature['geometry'] ?? [];
            $rings = match ($geometry['type'] ?? '') {
                'Polygon' => $geometry['coordinates'],
                'MultiPolygon' => array_merge(...$geometry['coordinates']),
                default => [],
            };
            foreach ($rings as $ring) {
                foreach ($ring as [$lng, $lat]) {
                    $minLng = min($minLng, $lng);
                    $maxLng = max($maxLng, $lng);
                    $minLat = min($minLat, $lat);
                    $maxLat = max($maxLat, $lat);
                }
            }
            $polygons[(string) $feature['properties']['istat']] = ['name' => (string) $feature['properties']['name'], 'rings' => $rings];
        }

        $kx = cos(deg2rad(($minLat + $maxLat) / 2));
        $scale = self::WIDTH / (($maxLng - $minLng) * $kx);
        $height = round(($maxLat - $minLat) * $scale, 1);

        $towns = [];
        foreach ($polygons as $istat => $polygon) {
            $d = '';
            $sumX = $sumY = 0.0;
            $count = 0;
            foreach ($polygon['rings'] as $ring) {
                $points = [];
                foreach ($ring as [$lng, $lat]) {
                    $x = round(($lng - $minLng) * $kx * $scale, 1);
                    $y = round(($maxLat - $lat) * $scale, 1);
                    $points[] = $x . ' ' . $y;
                    $sumX += $x;
                    $sumY += $y;
                    $count++;
                }
                $d .= 'M' . implode('L', $points) . 'Z';
            }
            $towns[$istat] = [
                'name' => $polygon['name'],
                'd' => $d,
                'cx' => $count > 0 ? round($sumX / $count, 1) : 0.0,
                'cy' => $count > 0 ? round($sumY / $count, 1) : 0.0,
            ];
        }

        return ['width' => self::WIDTH, 'height' => $height, 'towns' => $towns];
    }
}
