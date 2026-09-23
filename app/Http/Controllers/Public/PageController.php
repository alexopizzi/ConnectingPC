<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Settings\SettingsRepository;
use App\Http\Controller;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

/**
 * Pagine informative (Partecipa, Il progetto, Contatti, Privacy, Accessibilità).
 * Testi nelle stringhe UI (page.*): contenuti PROVVISORI da validare con il committente (titolare: D-025).
 */
final class PageController extends Controller
{
    /** Segmento URL => [chiave del titolo, template] */
    public const SECTIONS = [
        'partecipa' => ['nav.participate', 'pages/participate'],
        'progetto' => ['nav.project', 'pages/project'],
        'contatti' => ['nav.contacts', 'pages/contacts'],
        'privacy' => ['nav.privacy', 'pages/privacy'],
        'accessibilita' => ['nav.accessibility', 'pages/accessibility'],
        // Sezioni in preparazione (sostituite da rotte dedicate quando implementate)
        'associazioni-comunita' => ['nav.communities', 'public/section'],
        'mediatori' => ['nav.mediators', 'public/section'],
    ];

    public function section(Request $request): Response
    {
        [$titleKey, $template] = self::SECTIONS[(string) $request->attribute('section')] ?? throw new HttpException(404);

        return $this->render($template, [
            'pageTitle' => $this->t($titleKey),
            'managers' => (array) $this->container->get(SettingsRepository::class)->get('contacts.managers', []),
        ]);
    }
}
