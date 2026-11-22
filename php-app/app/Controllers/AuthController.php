<?php

declare(strict_types=1);

final class AuthController
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const THROTTLE_WINDOW_SECONDS = 300; // 5 minutes

    public function __construct(private User $userModel)
    {
    }

    public function showRegister(): void
    {
        AuthMiddleware::requireGuest();

        Response::view('pages/register', [
            'csrfToken' => Csrf::token(),
            'errors' => [],
            'old' => ['name' => '', 'email' => ''],
        ]);
    }

    public function register(): void
    {
        AuthMiddleware::requireGuest();

        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            Response::view('pages/register', [
                'csrfToken' => Csrf::token(),
                'errors' => ['Invalid security token. Please try again.'],
                'old' => ['name' => $name, 'email' => $email],
            ], 403);
            return;
        }

        $errors = [];

        if ($name === '' || mb_strlen($name, 'UTF-8') > 100) {
            $errors[] = 'Name is required (max 100 characters).';
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email, 'UTF-8') > 255) {
            $errors[] = 'A valid email address is required.';
        }

        if (mb_strlen($password, 'UTF-8') < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }

        if ($password !== $passwordConfirm) {
            $errors[] = 'Passwords do not match.';
        }

        if ($errors === [] && $this->userModel->findByEmail($email) !== null) {
            $errors[] = 'An account with this email already exists.';
        }

        if ($errors !== []) {
            Response::view('pages/register', [
                'csrfToken' => Csrf::token(),
                'errors' => $errors,
                'old' => ['name' => $name, 'email' => $email],
            ], 422);
            return;
        }

        $this->userModel->create($name, $email, $password);

        Session::set('flash_success', 'Account created successfully. You can now log in.');
        Response::redirect('/login');
    }

    public function showLogin(): void
    {
        AuthMiddleware::requireGuest();

        $successMessage = Session::get('flash_success');
        Session::remove('flash_success');

        Response::view('pages/login', [
            'csrfToken' => Csrf::token(),
            'errors' => [],
            'old' => ['email' => ''],
            'successMessage' => is_string($successMessage) ? $successMessage : null,
        ]);
    }

    public function login(): void
    {
        AuthMiddleware::requireGuest();

        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            Response::view('pages/login', [
                'csrfToken' => Csrf::token(),
                'errors' => [I18n::t('auth.error.csrf')],
                'old' => ['email' => $email],
                'successMessage' => null,
            ], 403);
            return;
        }

        if ($this->isThrottled()) {
            Response::view('pages/login', [
                'csrfToken' => Csrf::token(),
                'errors' => [I18n::t('auth.error.throttled')],
                'old' => ['email' => $email],
                'successMessage' => null,
            ], 429);
            return;
        }

        if ($email === '' || $password === '') {
            Response::view('pages/login', [
                'csrfToken' => Csrf::token(),
                'errors' => [I18n::t('auth.error.required')],
                'old' => ['email' => $email],
                'successMessage' => null,
            ], 422);
            return;
        }

        $user = $this->userModel->verifyCredentials($email, $password);

        if ($user === null) {
            $this->recordFailedAttempt();

            Response::view('pages/login', [
                'csrfToken' => Csrf::token(),
                'errors' => [I18n::t('auth.error.invalid_credentials')],
                'old' => ['email' => $email],
                'successMessage' => null,
            ], 401);
            return;
        }

        $this->clearFailedAttempts();
        Session::regenerate();
        Session::set('user', [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ]);

        $this->userModel->updateLastLogin((int) $user['id']);

        Response::redirect('/');
    }

    public function logout(): void
    {
        if (!Csrf::verify($_POST['_csrf_token'] ?? null)) {
            Response::html('<h1>403 - Invalid security token</h1>', 403);
            return;
        }

        Session::destroy();
        Response::redirect('/login');
    }

    private function isThrottled(): bool
    {
        $attempts = $this->recentFailedAttempts();

        return count($attempts) >= self::MAX_LOGIN_ATTEMPTS;
    }

    private function recordFailedAttempt(): void
    {
        $attempts = $this->recentFailedAttempts();
        $attempts[] = time();
        Session::set('login_attempts', $attempts);
    }

    private function clearFailedAttempts(): void
    {
        Session::remove('login_attempts');
    }

    /** @return int[] */
    private function recentFailedAttempts(): array
    {
        $raw = Session::get('login_attempts', []);
        if (!is_array($raw)) {
            return [];
        }

        $cutoff = time() - self::THROTTLE_WINDOW_SECONDS;

        return array_values(array_filter(
            $raw,
            static fn (mixed $timestamp): bool => is_int($timestamp) && $timestamp >= $cutoff
        ));
    }
}