<?php

declare(strict_types=1);

final class I18n
{
    private const SUPPORTED = ['en', 'fr'];
    private const DEFAULT_LOCALE = 'en';

    private static string $locale = self::DEFAULT_LOCALE;
    /** @var array<string, string> */
    private static array $messages = [];

    public static function init(): void
    {
        $saved = Session::get('locale');
        $locale = is_string($saved) && in_array($saved, self::SUPPORTED, true)
            ? $saved
            : self::DEFAULT_LOCALE;

        self::load($locale);
    }

    public static function setLocale(string $locale): void
    {
        if (!in_array($locale, self::SUPPORTED, true)) {
            $locale = self::DEFAULT_LOCALE;
        }

        Session::set('locale', $locale);
        self::load($locale);
    }

    public static function locale(): string
    {
        return self::$locale;
    }

    /**
     * @param array<string, string|int|float> $params
     */
    public static function t(string $key, array $params = []): string
    {
        $text = self::$messages[$key] ?? $key;

        foreach ($params as $name => $value) {
            $text = str_replace(':' . $name, (string) $value, $text);
        }

        return $text;
    }

    private static function load(string $locale): void
    {
        self::$locale = $locale;
        $path = dirname(__DIR__, 2) . '/lang/' . $locale . '.php';
        $loaded = is_file($path) ? require $path : [];
        self::$messages = is_array($loaded) ? $loaded : [];
    }
}