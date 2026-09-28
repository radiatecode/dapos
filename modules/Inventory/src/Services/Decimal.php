<?php

namespace DA\Inventory\Services;

final class Decimal
{
    public const SCALE = 4;

    public static function normalize(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.0000';
        }

        return bcadd((string) $value, '0', self::SCALE);
    }

    public static function add(mixed $left, mixed $right): string
    {
        return bcadd(self::normalize($left), self::normalize($right), self::SCALE);
    }

    public static function sub(mixed $left, mixed $right): string
    {
        return bcsub(self::normalize($left), self::normalize($right), self::SCALE);
    }

    public static function mul(mixed $left, mixed $right): string
    {
        return bcmul(self::normalize($left), self::normalize($right), self::SCALE);
    }

    public static function div(mixed $left, mixed $right): string
    {
        $divisor = self::normalize($right);

        if (bccomp($divisor, '0', self::SCALE) === 0) {
            return '0.0000';
        }

        return bcdiv(self::normalize($left), $divisor, self::SCALE);
    }

    public static function compare(mixed $left, mixed $right): int
    {
        return bccomp(self::normalize($left), self::normalize($right), self::SCALE);
    }

    public static function isZero(mixed $value): bool
    {
        return self::compare($value, '0') === 0;
    }

    public static function isPositive(mixed $value): bool
    {
        return self::compare($value, '0') === 1;
    }

    public static function isNegative(mixed $value): bool
    {
        return self::compare($value, '0') === -1;
    }

    public static function abs(mixed $value): string
    {
        $normalized = self::normalize($value);

        if (bccomp($normalized, '0', self::SCALE) < 0) {
            return bcmul($normalized, '-1', self::SCALE);
        }

        return $normalized;
    }
}
