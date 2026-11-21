<?php

declare(strict_types=1);

final class AuthMiddleware
{
    public static function requireAuth(): array
    {
        $user = Session::get('user');

        if (!is_array($user) || !isset($user['id'])) {
            Response::redirect('/login');
        }

        return $user;
    }

    public static function requireGuest(): void
    {
        $user = Session::get('user');

        if (is_array($user) && isset($user['id'])) {
            Response::redirect('/');
        }
    }
}