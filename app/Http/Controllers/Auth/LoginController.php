<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Auth\Auth;
use App\Auth\LoginResult;
use App\Auth\LoginService;
use App\Authorization\Gate;
use App\Http\Controller;
use App\Http\Request;
use App\Http\Response;

final class LoginController extends Controller
{
    public function show(Request $request): Response
    {
        $expired = $this->container->get(Auth::class)->sessionExpiredFlag();

        return $this->render('auth/login', ['pageTitle' => $this->t('auth.login.title'), 'expired' => $expired]);
    }

    public function login(Request $request): Response
    {
        $email = $request->string('email');
        $result = $this->container->get(LoginService::class)->attempt($email, $request->raw('password'), $request->ip());

        if ($result !== LoginResult::Success) {
            $message = match ($result) {
                LoginResult::Throttled => 'auth.login.throttled',
                LoginResult::Inactive => 'auth.login.inactive',
                LoginResult::NoAccess => 'auth.login.no_access',
                default => 'auth.login.invalid',
            };

            return $this->backWithErrors($request, $this->view()->route('auth.login'), ['form' => [$this->t($message)]]);
        }

        $auth = $this->container->get(Auth::class);
        $user = $auth->user();
        $intended = $auth->pullIntended();
        if ($intended !== null) {
            return Response::redirect($intended);
        }

        return $this->container->get(Gate::class)->allows($user, 'admin.access')
            ? $this->redirectTo('admin.dashboard')
            : $this->redirectTo('portal.dashboard');
    }

    public function logout(Request $request): Response
    {
        $this->container->get(Auth::class)->user();
        $this->container->get(Auth::class)->logout();
        $this->flash('success', $this->t('auth.logout.done'));

        return $this->redirectTo('auth.login');
    }
}
