<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Core\App;
use App\Core\Env;
use App\Core\Logger;
use App\Http\Controller;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

/**
 * Proxy con cache delle tile OpenStreetMap (vault "61", Q-06): l'IP dei visitatori non arriva a terze parti
 * e il traffico verso OSM si riduce. Limitato al riquadro della provincia di Piacenza e ai livelli 8–18.
 * Si attiva impostando MAP_TILE_URL=/tiles/{z}/{x}/{y}.png.
 */
final class TileController extends Controller
{
    private const MIN_ZOOM = 8;
    private const MAX_ZOOM = 18;
    /** Riquadro utile (D-024) con margine: [sud, ovest, nord, est] */
    private const BBOX = [44.45, 9.0, 45.30, 10.25];
    private const CACHE_DAYS = 30;

    public function show(Request $request): Response
    {
        $z = (int) $request->attribute('z');
        $x = (int) $request->attribute('x');
        $y = (int) $request->attribute('y');
        if ($z < self::MIN_ZOOM || $z > self::MAX_ZOOM || !$this->insideBoundingBox($z, $x, $y)) {
            throw new HttpException(404);
        }

        $file = APP_BASE_PATH . "/storage/tiles/$z/$x/$y.png";
        if (!is_file($file) || filemtime($file) < time() - self::CACHE_DAYS * 86400) {
            $png = $this->fetch($z, $x, $y);
            if ($png === null) {
                if (!is_file($file)) {
                    throw new HttpException(503);
                }
            } else {
                @mkdir(dirname($file), 0775, true);
                file_put_contents($file, $png, LOCK_EX);
            }
        }

        return new Response((string) file_get_contents($file), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }

    private function fetch(int $z, int $x, int $y): ?string
    {
        $upstream = str_replace(['{z}', '{x}', '{y}'], [(string) $z, (string) $x, (string) $y],
            (string) Env::get('MAP_TILE_UPSTREAM', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'));
        $handle = curl_init($upstream);
        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => false,
            // Policy OSMF: User-Agent identificativo e Referer del sito
            CURLOPT_USERAGENT => 'ConnectingPC/' . App::version() . ' (tile proxy; ' . Env::get('APP_URL', '') . ')',
            CURLOPT_REFERER => (string) Env::get('APP_URL', ''),
        ]);
        $body = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $type = (string) curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
        curl_close($handle);

        if (!is_string($body) || $status !== 200 || !str_starts_with($type, 'image/png')) {
            $this->container->get(Logger::class)->warning('Tile non disponibile', ['status' => $status, 'z' => $z]);

            return null;
        }

        return $body;
    }

    private function insideBoundingBox(int $z, int $x, int $y): bool
    {
        [$south, $west, $north, $east] = self::BBOX;
        [$minX, $minY] = self::tile($north, $west, $z);
        [$maxX, $maxY] = self::tile($south, $east, $z);

        // Margine di 2 tile per riempire lo schermo ai bordi della provincia
        $margin = 2;

        return $x >= $minX - $margin && $x <= $maxX + $margin && $y >= $minY - $margin && $y <= $maxY + $margin;
    }

    /** @return array{0: int, 1: int} */
    private static function tile(float $lat, float $lng, int $z): array
    {
        $n = 2 ** $z;
        $x = (int) floor(($lng + 180) / 360 * $n);
        $rad = deg2rad($lat);
        $y = (int) floor((1 - log(tan($rad) + 1 / cos($rad)) / M_PI) / 2 * $n);

        return [$x, $y];
    }
}
