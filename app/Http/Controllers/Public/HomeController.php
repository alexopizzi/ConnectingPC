<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Domain\Catalog\CatalogRepository;
use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;
use App\I18n\LocaleRegistry;

final class HomeController extends Controller
{
    /** "/" → reindirizza alla lingua del browser, se pubblica, altrimenti a quella predefinita (senza cookie). */
    public function root(Request $request): Response
    {
        $locale = $this->container->get(LocaleRegistry::class)->negotiate($request->acceptedLanguages());

        return Response::redirect($this->view()->route('public.home', ['locale' => $locale]), 302)
            ->withHeader('Vary', 'Accept-Language')
            ->withHeader('Cache-Control', 'private, no-cache');
    }

    public function index(Request $request): Response
    {
        $catalog = $this->container->get(CatalogRepository::class);
        $this->view()->share('spoken_languages', $catalog->spokenLanguages());

        return $this->render('public/home', [
            'pageTitle' => null,
            'needs' => $catalog->needs($this->view()->locale(), true),
        ]);
    }
}
