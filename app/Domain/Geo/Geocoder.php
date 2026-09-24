<?php

declare(strict_types=1);

namespace App\Domain\Geo;

use App\Core\App;
use App\Core\FileCache;

/**
 * Geocodifica assistita degli indirizzi (RF-40, vault "61"): chiamata server-side a Nominatim nel rispetto
 * della sua policy (una richiesta al secondo, User-Agent ed email identificativi, cache dei risultati),
 * risultati limitati al riquadro della provincia. La posizione proposta va sempre confermata a mano.
 */
final class Geocoder
{
    private const CACHE_PREFIX = 'geocode.';
    private const CACHE_TTL = 2592000; // 30 giorni
    /** Riquadro della provincia di Piacenza (D-024): lng min, lat max, lng max, lat min */
    private const VIEWBOX = '9.15,45.20,10.10,44.55';

    public function __construct(
        private readonly FileCache $cache,
        private readonly string $endpoint,
        private readonly string $contactEmail,
        private readonly string $appUrl,
        private readonly string $lockFile,
    ) {
    }

    /**
     * @return list<array{label: string, lat: float, lng: float}> al massimo 5 proposte
     */
    public function search(string $address, string $postalCode, string $town): array
    {
        $query = trim(implode(', ', array_filter([trim($address), trim($postalCode . ' ' . $town), 'Provincia di Piacenza', 'Italia'])));
        if (mb_strlen(trim($address)) < 3) {
            return [];
        }
        $cacheKey = self::CACHE_PREFIX . hash('sha256', mb_strtolower($query));
        $cached = $this->cache->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $this->throttle();
        $url = $this->endpoint . '?' . http_build_query([
            'q' => $query, 'format' => 'jsonv2', 'limit' => 5, 'countrycodes' => 'it',
            'viewbox' => self::VIEWBOX, 'bounded' => 1, 'email' => $this->contactEmail,
        ]);
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_USERAGENT => 'ConnectingPC/' . App::version() . ' (geocoding; ' . $this->appUrl . ')',
            CURLOPT_REFERER => $this->appUrl,
            CURLOPT_HTTPHEADER => ['Accept-Language: it'],
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);
        if (!is_string($body) || $status !== 200) {
            throw new \RuntimeException('geocoding_unavailable');
        }

        $results = [];
        foreach ((array) json_decode($body, true) as $item) {
            if (!is_array($item) || !isset($item['lat'], $item['lon'])) {
                continue;
            }
            $results[] = [
                'label' => (string) ($item['display_name'] ?? ''),
                'lat' => round((float) $item['lat'], 6),
                'lng' => round((float) $item['lon'], 6),
            ];
        }
        $this->cache->set($cacheKey, $results, self::CACHE_TTL);

        return $results;
    }

    /** Al massimo una richiesta al secondo verso Nominatim, anche fra processi diversi (lock su file). */
    private function throttle(): void
    {
        $handle = fopen($this->lockFile, 'c+');
        if ($handle === false) {
            return;
        }
        flock($handle, LOCK_EX);
        $last = (float) stream_get_contents($handle);
        $wait = 1.0 - (microtime(true) - $last);
        if ($wait > 0) {
            usleep((int) ($wait * 1_000_000));
        }
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, (string) microtime(true));
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}
