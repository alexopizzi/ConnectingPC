<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Management\UiStringEditor;
use App\Http\Controllers\Manage\ManagementController;
use App\Http\Request;
use App\Http\Response;

/** Traduzioni delle stringhe dell'interfaccia (RF-36). Permessi in UiStringEditor (per lingua). */
final class UiStringController extends ManagementController
{
    protected function area(): string
    {
        return 'admin';
    }

    public function index(Request $request): Response
    {
        $editor = $this->container->get(UiStringEditor::class);
        $locales = array_values(array_filter($this->contentLocales(), fn (string $l): bool => $l !== 'it' && $editor->canEdit($this->user($request), $l)));
        $locale = in_array($request->string('lingua'), $locales, true) ? $request->string('lingua') : ($locales[0] ?? '');
        $filter = in_array($request->string('stato'), UiStringEditor::FILTERS, true) ? $request->string('stato') : 'to_review';
        $query = mb_substr($request->string('q'), 0, 100);

        return $this->page('admin/tools/strings', [
            'pageTitle' => $this->t('admin.strings.title'),
            'locales' => $locales,
            'locale' => $locale,
            'filter' => $filter,
            'query' => $query,
            'strings' => $locale === '' ? [] : $editor->list($locale, $filter, $query),
            'summary' => $locale === '' ? [] : $editor->summary($locale),
        ]);
    }

    public function save(Request $request): Response
    {
        $locale = $request->string('locale');

        return $this->attempt(
            fn () => $this->container->get(UiStringEditor::class)->save($this->user($request), (int) $request->attribute('id'), $locale, $request->raw('text'), $request->string('approve') === '1'),
            'manage.saved', 'strings.index', array_filter(['lingua' => $locale, 'stato' => $request->string('stato'), 'q' => $request->string('q')]),
        );
    }
}
