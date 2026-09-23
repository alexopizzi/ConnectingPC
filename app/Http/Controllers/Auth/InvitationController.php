<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\AccountService;
use App\Core\Session;
use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;

/** Attivazione dell'account da invito: l'utente sceglie la propria password (vault "50"). */
final class InvitationController extends Controller
{
    private const SESSION_TOKEN = 'auth.invite_token';

    public function show(Request $request): Response
    {
        $session = $this->container->get(Session::class);
        $token = $request->query['token'] ?? null;
        if (is_string($token)) {
            $session->set(self::SESSION_TOKEN, $token);

            return $this->redirectTo('auth.invitation')->withHeader('Referrer-Policy', 'no-referrer');
        }

        $stored = $session->get(self::SESSION_TOKEN);
        $user = is_string($stored) ? $this->container->get(AccountService::class)->invitationUser($stored) : null;

        return $this->render('auth/invitation', [
            'pageTitle' => $this->t('auth.invite.title'),
            'invitedUser' => $user,
        ])->withHeader('Referrer-Policy', 'no-referrer');
    }

    public function accept(Request $request): Response
    {
        $session = $this->container->get(Session::class);
        $token = (string) $session->get(self::SESSION_TOKEN, '');
        $errors = $this->container->get(AccountService::class)
            ->acceptInvitation($token, $request->raw('password'), $request->raw('password_confirmation'));

        if ($errors !== []) {
            return $this->backWithErrors($request, $this->view()->route('auth.invitation'), $this->translateErrors(['password' => $errors]));
        }
        $session->forget(self::SESSION_TOKEN);
        $this->flash('success', $this->t('auth.invite.done'));

        return $this->redirectTo('auth.login');
    }
}
