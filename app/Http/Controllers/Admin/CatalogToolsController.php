<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Audit\AuditLogger;
use App\Authorization\Gate;
use App\Core\Validator;
use App\Domain\Management\SynonymEditor;
use App\Domain\Settings\SettingsRepository;
use App\Http\Controllers\Manage\ManagementController;
use App\Http\Request;
use App\Http\Response;

/**
 * Strumenti del catalogo in admin: dizionario dei sinonimi (taxonomy.manage), qualità dei dati
 * (quality.view), impostazioni dei recapiti dei gestori (settings.manage). Permessi anche sulle rotte.
 */
final class CatalogToolsController extends ManagementController
{
    protected function area(): string
    {
        return 'admin';
    }

    public function synonyms(Request $request): Response
    {
        $locale = in_array($request->string('lingua'), $this->contentLocales(), true) ? $request->string('lingua') : '';
        $needId = $request->int('bisogno');

        return $this->page('admin/tools/synonyms', [
            'pageTitle' => $this->t('admin.synonyms.title'),
            'terms' => $this->container->get(SynonymEditor::class)->list($locale, $needId),
            'filters' => ['locale' => $locale, 'need' => $needId],
            'needs' => $this->management()->needs(),
            'locales' => $this->contentLocales(),
        ]);
    }

    public function addSynonym(Request $request): Response
    {
        return $this->attempt(
            fn () => $this->container->get(SynonymEditor::class)->add($this->user($request), $request->string('locale'), $request->string('term'), $request->int('need_id'), $request->int('weight', 8)),
            'manage.saved', 'synonyms.index', array_filter(['lingua' => $request->string('locale'), 'bisogno' => $request->int('need_id')]),
        );
    }

    public function deleteSynonym(Request $request): Response
    {
        return $this->attempt(
            fn () => $this->container->get(SynonymEditor::class)->delete($this->user($request), (int) $request->attribute('id')),
            'manage.deleted', 'synonyms.index',
        );
    }

    public function recentChanges(Request $request): Response
    {
        $days = in_array($request->int('giorni', 7), [1, 7, 30, 90], true) ? $request->int('giorni', 7) : 7;
        $organizationId = $request->int('organizzazione');

        return $this->page('admin/tools/recent-changes', [
            'pageTitle' => $this->t('admin.recent.title'),
            'changes' => $this->management()->recentChanges($organizationId, $days),
            'filters' => ['days' => $days, 'organization' => $organizationId],
            'organizations' => $this->management()->organizationOptions(),
        ]);
    }

    public function quality(Request $request): Response
    {
        return $this->page('admin/tools/quality', [
            'pageTitle' => $this->t('admin.quality.title'),
            'report' => $this->management()->qualityReport(),
        ]);
    }

    public function settings(Request $request): Response
    {
        return $this->page('admin/tools/settings', [
            'pageTitle' => $this->t('admin.settings.title'),
            'managers' => (array) $this->container->get(SettingsRepository::class)->get('contacts.managers', []),
        ]);
    }

    public function saveSettings(Request $request): Response
    {
        $this->container->get(Gate::class)->authorize($this->user($request), 'settings.manage');
        $errors = Validator::validate($request->body, [
            'name' => ['max:190'], 'email' => ['email', 'max:254'], 'phone' => ['max:40'], 'hours' => ['max:190'], 'address' => ['max:255'],
        ]);
        if ($errors !== []) {
            return $this->backWithErrors($request, $this->view()->route('admin.settings.index'), $this->translateErrors($errors));
        }
        $settings = $this->container->get(SettingsRepository::class);
        $before = (array) $settings->get('contacts.managers', []);
        $after = [
            'name' => $request->string('name'), 'email' => $request->string('email'),
            'phone' => $request->string('phone'), 'hours' => $request->string('hours'), 'address' => $request->string('address'),
        ];
        $settings->set('contacts.managers', $after, (int) $this->user($request)['id']);
        $this->container->get(AuditLogger::class)->log('settings.updated', 'setting', null, ['contacts.managers' => AuditLogger::diff($before, $after)]);
        $this->flash('success', $this->t('manage.saved'));

        return $this->redirectTo('admin.settings.index');
    }
}
