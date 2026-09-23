<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AccountService;
use App\Core\Session;
use App\Core\Validator;
use App\Http\Controller;
use App\Http\HttpException;
use App\Http\Request;
use App\Http\Response;

/**
 * Recupero password. Il token del link viene spostato subito in sessione e l'URL ripulito,
 * così non resta nella cronologia né passa nel Referer (vault "72 - Sicurezza").
 */
final class PasswordController extends Controller
{
    private const SESSION_TOKEN = 'auth.reset_token';

    public function forgot(Request $request): Response
    {
        return $this->render('auth/forgot', ['pageTitle' => $this->t('auth.forgot.title')]);
    }

    public function sendLink(Request $request): Response
    {
        $errors = Validator::validate($request->body, ['email' => ['required', 'email', 'max:254']]);
        if ($errors !== []) {
            return $this->backWithErrors($request, $this->view()->route('auth.forgot'), $this->translateErrors($errors));
        }
        if (!$this->container->get(AccountService::class)->requestPasswordReset($request->string('email'), $request->ip())) {
            throw new HttpException(429);
        }
        $this->flash('success', $this->t('auth.forgot.sent'));

        return $this->redirectTo('auth.login');
    }

    public function resetForm(Request $request): Response
    {
        $session = $this->container->get(Session::class);
        $token = $request->query['token'] ?? null;
        if (is_string($token)) {
            $session->set(self::SESSION_TOKEN, $token);

            return $this->redirectTo('auth.reset')->withHeader('Referrer-Policy', 'no-referrer');
        }

        $stored = $session->get(self::SESSION_TOKEN);
        $valid = is_string($stored) && $this->container->get(AccountService::class)->resetTokenIsValid($stored);

        return $this->render('auth/reset', ['pageTitle' => $this->t('auth.reset.title'), 'valid' => $valid])
            ->withHeader('Referrer-Policy', 'no-referrer');
    }

    public function reset(Request $request): Response
    {
        $session = $this->container->get(Session::class);
        $token = (string) $session->get(self::SESSION_TOKEN, '');
        $errors = $this->container->get(AccountService::class)
            ->resetPassword($token, $request->raw('password'), $request->raw('password_confirmation'));

        if ($errors !== []) {
            return $this->backWithErrors($request, $this->view()->route('auth.reset'), $this->translateErrors(['password' => $errors]));
        }
        $session->forget(self::SESSION_TOKEN);
        $this->flash('success', $this->t('auth.reset.done'));

        return $this->redirectTo('auth.login');
    }
}
