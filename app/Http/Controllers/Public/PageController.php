<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controller;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

/**
 * Sezioni pubbliche previste dall'architettura e non ancora implementate: pagina "in preparazione"
 * invece di un 404, così la navigazione è già completa (sostituite nelle versioni 0.4–0.9).
 */
final class PageController extends Controller
{
    /** Segmento URL => chiave di traduzione del titolo */
    public const SECTIONS = [
        'servizi' => 'nav.services',
        'mappa' => 'nav.map',
        'associazioni-comunita' => 'nav.communities',
        'mediatori' => 'nav.mediators',
        'partecipa' => 'nav.participate',
        'progetto' => 'nav.project',
        'contatti' => 'nav.contacts',
        'privacy' => 'nav.privacy',
        'accessibilita' => 'nav.accessibility',
    ];

    public function section(Request $request): Response
    {
        $key = self::SECTIONS[(string) $request->attribute('section')] ?? throw new HttpException(404);

        return $this->render('public/section', ['pageTitle' => $this->t($key)]);
    }

    public function search(Request $request): Response
    {
        $query = mb_substr($request->string('q'), 0, 200);

        return $this->render('public/search', ['pageTitle' => $this->t('nav.search'), 'query' => $query]);
    }
}
