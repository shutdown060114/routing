<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Audit;
use App\Core\Csrf;
use App\Core\View;

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
        $password = $_POST['password'] ?? '';

        if (Auth::attempt($GLOBALS['pdo'], $username, $password)) {
            Audit::log('auth.login', 'user', Auth::id());
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
        if (Auth::id()) Audit::log('auth.logout', 'user', Auth::id());
        Auth::logout();
        redirect('/login');
    }
}
