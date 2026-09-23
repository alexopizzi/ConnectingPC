<?php

declare(strict_types=1);

namespace App\Auth;

use App\Core\Mailer;
use App\Core\UrlGenerator;
use App\I18n\LocaleRegistry;
use App\I18n\Translator;

/**
 * Email dell'account (invito, recupero password, password cambiata) nella lingua dell'utente.
 * Testi in lang/*.php (chiavi email.*), link assoluti costruiti da APP_URL.
 */
final class AccountMailer
{
    public function __construct(
        private readonly Mailer $mailer,
        private readonly Translator $translator,
        private readonly UrlGenerator $url,
        private readonly LocaleRegistry $locales,
    ) {
    }

    /** @param array<string, mixed> $user */
    public function invitation(array $user, string $token): bool
    {
        $locale = $this->locale($user);
        $link = $this->url->absoluteRoute('auth.invitation', ['locale' => $locale, 'token' => $token]);

        return $this->send($user, $locale, 'email.invite', [
            'name' => (string) $user['display_name'],
            'link' => $link,
            'hours' => (string) intdiv(TokenService::INVITE_TTL, 3600),
        ]);
    }

    /** @param array<string, mixed> $user */
    public function passwordReset(array $user, string $token): bool
    {
        $locale = $this->locale($user);
        $link = $this->url->absoluteRoute('auth.reset', ['locale' => $locale, 'token' => $token]);

        return $this->send($user, $locale, 'email.reset', [
            'name' => (string) $user['display_name'],
            'link' => $link,
            'minutes' => (string) intdiv(TokenService::PASSWORD_RESET_TTL, 60),
        ]);
    }

    /** @param array<string, mixed> $user */
    public function passwordChanged(array $user): bool
    {
        $locale = $this->locale($user);

        return $this->send($user, $locale, 'email.password_changed', ['name' => (string) $user['display_name']]);
    }

    /**
     * @param array<string, mixed> $user
     * @param array<string, string> $params
     */
    private function send(array $user, string $locale, string $prefix, array $params): bool
    {
        $subject = $this->translator->get($prefix . '.subject', $params, $locale);
        $body = $this->translator->get($prefix . '.body', $params, $locale)
            . "\n\n— " . $this->translator->get('app.name', [], $locale);

        return $this->mailer->send((string) $user['email'], $subject, $body);
    }

    /** @param array<string, mixed> $user */
    private function locale(array $user): string
    {
        $preferred = $user['preferred_locale'] ?? null;

        return is_string($preferred) && $this->locales->isEnabled($preferred) ? $preferred : $this->locales->default();
    }
}
