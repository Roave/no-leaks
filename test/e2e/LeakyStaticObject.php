<?php

declare(strict_types=1);

namespace RoaveE2ETest\NoLeaks\PHPUnit;

/** @psalm-external-mutation-free */
final class LeakyStaticObject
{
    /** @var array<int, mixed> */
    public static array $memoryLeakingStupidMistake = [];

    public static function leak(mixed $value): void
    {
        /** @psalm-suppress MixedAssignment */
        self::$memoryLeakingStupidMistake[] = $value;
    }
}
