<?php

namespace App\Support;

final class PhoneNumber
{
    public const MIN_DIGITS = 10;

    public const MAX_DIGITS = 11;

    public static function normalize(?string $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    public static function isValid(?string $value): bool
    {
        $digits = self::normalize($value);

        return strlen($digits) >= self::MIN_DIGITS && strlen($digits) <= self::MAX_DIGITS;
    }
}
