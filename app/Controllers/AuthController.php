<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Services\AuditService;

final class AuthController
{
    public function loginForm(): void
    {
        if (Auth::check()) redirect('/');
        View::render('auth/login', ['title' => 'Login']);
    }

    public function login(): void
    {
        Csrf::validate();
        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if (Auth::attempt($username, $password)) {
            AuditService::log('auth.login', 'user', Auth::id());
            redirect('/');
        }

        View::render('auth/login', [
            'title' => 'Login',
            'error' => 'Invalid username or password.',
            'username' => $username,
        ]);
    }

    public function logout(): void
    {
        Csrf::validate();
        if (Auth::id()) AuditService::log('auth.logout', 'user', Auth::id());
        Auth::logout();
        redirect('/login');
    }
}
