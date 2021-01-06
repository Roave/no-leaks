<?php

declare(strict_types=1);

namespace RoaveE2ETest\NoLeaks\PHPUnit;

/** @psalm-external-mutation-free */
final class LeakyStaticObject
{
    /** @var array<int, mixed> */
    public static $memoryLeakingStupidMistake = [];

    /** @param mixed $value */
    public static function leak($value): void
    {
        self::$memoryLeakingStupidMistake[] = $value;
    }
}
