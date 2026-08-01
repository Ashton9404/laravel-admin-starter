<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * One list of supported locales, shared by the middleware, the validation rules
 * and the front-end. Adding a language should mean editing this file and adding
 * the matching lang/ directory — nothing else.
 */
final class Locales
{
    public const DEFAULT = 'en';

    /**
     * @var array<string, string> locale => endonym shown in the switcher
     */
    public const SUPPORTED = [
        'en' => 'English',
        'zh-TW' => '繁體中文',
    ];

    /**
     * @return array<int, string>
     */
    public static function codes(): array
    {
        return array_keys(self::SUPPORTED);
    }

    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, self::SUPPORTED);
    }

    /**
     * Pick the best supported locale for an Accept-Language header.
     *
     * Deliberately simple: match the exact tag first, then the primary subtag,
     * so "zh-TW,zh;q=0.9" finds Traditional Chinese and "zh-CN" does not.
     */
    public static function fromAcceptLanguage(?string $header): ?string
    {
        if (blank($header)) {
            return null;
        }

        foreach (explode(',', $header) as $part) {
            $tag = trim(Str::before($part, ';'));

            foreach (self::codes() as $code) {
                if (strcasecmp($tag, $code) === 0) {
                    return $code;
                }
            }
        }

        return null;
    }
}
