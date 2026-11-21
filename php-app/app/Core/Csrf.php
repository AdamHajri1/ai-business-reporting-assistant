<?php

declare(strict_types=1);

final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public static function token(): string
    {
        Session::start();

        $token = Session::get(self::SESSION_KEY);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_KEY, $token);
        }

        return $token;
    }

    public static function verify(?string $submittedToken): bool
    {
        Session::start();

        $storedToken = Session::get(self::SESSION_KEY);
        if (!is_string($storedToken) || !is_string($submittedToken) || $submittedToken === '') {
            return false;
        }

        return hash_equals($storedToken, $submittedToken);
    }
}