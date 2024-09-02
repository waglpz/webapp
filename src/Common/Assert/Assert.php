<?php

declare(strict_types=1);

namespace Waglpz\Webapp\Common\Assert;

final class Assert extends \Webmozart\Assert\Assert
{
    public static function dateString(mixed $value, string $format = 'Y-m-d H:i:s', string|null $message = null): void
    {
        self::string($value);

        try {
            $date = new \DateTimeImmutable($value);
        } catch (\Throwable $exception) {
            self::reportInvalidArgument(
                \sprintf(
                    $message ?? 'Expected a string with valid datetime. Got: "%s". More details %s',
                    $value,
                    $exception->getMessage(),
                ),
            );
        }

        $expected = $date->format($format);
        if ($value === $expected) {
            return;
        }

        self::reportInvalidArgument(
            \sprintf(
                $message ?? 'Expected a string with valid datetime. Got: "%s". Expected same date after formatting.',
                $value,
            ),
        );
    }
}
