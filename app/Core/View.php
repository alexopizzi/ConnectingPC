<?php

declare(strict_types=1);

namespace App\Core;

use App\I18n\LocaleRegistry;
use App\I18n\Translator;
use App\Security\Csrf;
use InvalidArgumentException;
use Throwable;

/**
 * Motore di template in PHP puro. Nei template `$this` è la View: usare sempre $this->e() per l'output
 * e $this->t() per i testi (nessuna stringa visibile scritta a mano, vault "60").
 */
final class View
{
    /** @var array<string, mixed> */
    private array $shared = [];

    public function __construct(
        private readonly string $directory,
        private readonly Translator $translator,
        private readonly UrlGenerator $url,
        private readonly LocaleRegistry $locales,
        private readonly Session $session,
        private readonly Csrf $csrf,
    ) {
    }

    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    public function shared(string $key, mixed $default = null): mixed
    {
        return $this->shared[$key] ?? $default;
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = [], ?string $layout = null): string
    {
        $content = $this->renderFile($template, $data);
        if ($layout === null) {
            return $content;
        }

        return $this->renderFile($layout, [...$data, 'content' => $content]);
    }

    /** @param array<string, mixed> $data */
    public function partial(string $template, array $data = []): string
    {
        return $this->renderFile('partials/' . $template, $data);
    }

    // --- Helper per i template -------------------------------------------------------------

    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** @param array<string, string|int|float> $params */
    public function t(string $key, array $params = []): string
    {
        return $this->translator->get($key, $params);
    }

    public function locale(): string
    {
        return $this->translator->locale();
    }

    public function dir(): string
    {
        return $this->locales->direction($this->locale());
    }

    /**
     * URL di una rotta; il parametro {locale} è aggiunto automaticamente con la lingua corrente.
     *
     * @param array<string, string|int> $params
     */
    public function route(string $name, array $params = []): string
    {
        if (!isset($params['locale']) && in_array('locale', $this->url->router()->parameters($name), true)) {
            $params['locale'] = $this->locale();
        }

        return $this->url->route($name, $params);
    }

    public function asset(string $path): string
    {
        return $this->url->asset($path);
    }

    /** URL della pagina corrente in un'altra lingua (selettore lingua). */
    public function switchLocaleUrl(string $locale): string
    {
        $name = $this->shared('route_name');
        $params = (array) $this->shared('route_params', []);
        if (!is_string($name) || !isset($params['locale'])) {
            return $this->url->route('public.home', ['locale' => $locale]);
        }
        $query = (array) $this->shared('query_params', []);

        return $this->url->route($name, [...$query, ...$params, 'locale' => $locale]);
    }

    /** @return array<string, array{code: string, native_name: string, direction: string}> */
    public function publicLocales(): array
    {
        return $this->locales->public();
    }

    public function csrfField(): string
    {
        return '<input type="hidden" name="' . Csrf::FIELD . '" value="' . $this->e($this->csrf->token()) . '">';
    }

    /** Valore inviato nel modulo precedente (dopo un errore di validazione). */
    public function old(string $field, string $default = ''): string
    {
        if (!$this->session->isStarted()) {
            return $default;
        }
        $old = (array) $this->session->getFlash('_old', []);

        return isset($old[$field]) && is_scalar($old[$field]) ? (string) $old[$field] : $default;
    }

    /** @return list<string> messaggi di errore (già tradotti) per il campo */
    public function errors(string $field): array
    {
        if (!$this->session->isStarted()) {
            return [];
        }
        $errors = (array) $this->session->getFlash('_errors', []);

        return array_values((array) ($errors[$field] ?? []));
    }

    /** @return list<array{type: string, message: string}> */
    public function flashes(): array
    {
        if (!$this->session->isStarted()) {
            return [];
        }

        return array_values((array) $this->session->getFlash('_messages', []));
    }

    /** Data/ora UTC del database mostrata nel fuso di visualizzazione e nella lingua corrente. */
    public function datetime(?string $utc, int $dateStyle = \IntlDateFormatter::MEDIUM, int $timeStyle = \IntlDateFormatter::SHORT): string
    {
        if ($utc === null || $utc === '') {
            return '';
        }
        try {
            $date = new \DateTimeImmutable($utc, new \DateTimeZone('UTC'));
        } catch (Throwable) {
            return $utc;
        }
        $formatter = new \IntlDateFormatter($this->locale(), $dateStyle, $timeStyle, Env::get('APP_TIMEZONE', 'Europe/Rome'));

        return (string) $formatter->format($date);
    }

    // --- Contenuti multilingua (vault "60") -----------------------------------------------

    /**
     * Testo breve localizzato; se è un ripiego in altra lingua viene marcato con lang/dir.
     *
     * @param array{text: string, lang: string, fallback: bool}|null $value
     */
    public function localized(?array $value): string
    {
        if ($value === null) {
            return '';
        }
        $text = $this->e($value['text']);
        if (!$value['fallback']) {
            return $text;
        }

        return '<span lang="' . $this->e($value['lang']) . '" dir="' . $this->e($this->locales->direction($value['lang'])) . '">' . $text . '</span>';
    }

    /**
     * Testo lungo: paragrafi e righe che iniziano con "- " come elenco puntato. Nessun HTML dell'utente.
     *
     * @param array{text: string, lang: string, fallback: bool}|null $value
     */
    public function richText(?array $value): string
    {
        if ($value === null) {
            return '';
        }
        $html = '';
        $list = [];
        $flush = function () use (&$list, &$html): void {
            if ($list !== []) {
                $html .= '<ul>' . implode('', array_map(fn (string $i): string => '<li>' . $this->e($i) . '</li>', $list)) . '</ul>';
                $list = [];
            }
        };
        foreach (preg_split('/\R/u', trim($value['text'])) ?: [] as $line) {
            $line = trim($line);
            if (str_starts_with($line, '- ')) {
                $list[] = substr($line, 2);
                continue;
            }
            $flush();
            if ($line !== '') {
                $html .= '<p>' . $this->e($line) . '</p>';
            }
        }
        $flush();

        $attributes = $value['fallback']
            ? ' lang="' . $this->e($value['lang']) . '" dir="' . $this->e($this->locales->direction($value['lang'])) . '"'
            : '';

        return '<div class="rich-text"' . $attributes . '>' . $html . '</div>';
    }

    /** Nome di una lingua parlata nella lingua dell'interfaccia (ICU). */
    public function languageName(string $code): string
    {
        $name = \Locale::getDisplayLanguage($code, $this->locale());
        $name = $name === '' || $name === $code ? $code : $name;

        return mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1);
    }

    /** Nome del giorno della settimana (1 = lunedì) nella lingua corrente. */
    public function weekdayName(int $weekday): string
    {
        $formatter = new \IntlDateFormatter($this->locale(), \IntlDateFormatter::NONE, \IntlDateFormatter::NONE, 'UTC', null, 'EEEE');
        // 2024-01-01 era un lunedì
        $date = new \DateTimeImmutable('2024-01-0' . max(1, min(7, $weekday)) . ' 12:00:00', new \DateTimeZone('UTC'));
        $name = (string) $formatter->format($date);

        return mb_strtoupper(mb_substr($name, 0, 1)) . mb_substr($name, 1);
    }

    /**
     * Stato di apertura in questo momento (fuso di visualizzazione).
     *
     * @param list<array{weekday: int, opens: string, closes: string, appointment: bool}> $hours
     * @return array{open: bool, until: ?string}|null null se non ci sono orari
     */
    public function openingStatus(array $hours): ?array
    {
        if ($hours === []) {
            return null;
        }
        $now = new \DateTimeImmutable('now', new \DateTimeZone(Env::get('APP_TIMEZONE', 'Europe/Rome')));
        $day = (int) $now->format('N');
        $time = $now->format('H:i');
        foreach ($hours as $slot) {
            if ($slot['weekday'] === $day && $slot['opens'] <= $time && $time < $slot['closes']) {
                return ['open' => true, 'until' => $slot['closes']];
            }
        }

        return ['open' => false, 'until' => null];
    }

    public function icon(string $name): string
    {
        static $icons = null;
        $icons ??= require APP_BASE_PATH . '/config/icons.php';

        return $icons[$name] ?? $icons['default'];
    }

    // --- Interni --------------------------------------------------------------------------

    /** @param array<string, mixed> $data */
    private function renderFile(string $template, array $data): string
    {
        if (!preg_match('#^[a-z0-9_/-]+$#', $template)) {
            throw new InvalidArgumentException('Nome di template non valido: ' . $template);
        }
        $file = $this->directory . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new InvalidArgumentException('Template inesistente: ' . $template);
        }

        $render = function (string $__file, array $__data): string {
            extract($__data, EXTR_SKIP);
            ob_start();
            try {
                include $__file;
            } catch (Throwable $e) {
                ob_end_clean();
                throw $e;
            }

            return (string) ob_get_clean();
        };

        return $render->call($this, $file, [...$this->shared, ...$data]);
    }
}
