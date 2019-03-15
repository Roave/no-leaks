<?php

declare(strict_types=1);

namespace RoaveUnitTest\NoLeaks\PHPUnit;

use PHPUnit\Framework\TestCase;
use Roave\NoLeaks\PHPUnit\EmptyBaselineMemoryUsageTest as Baseline;
use function memory_get_usage;

/** @covers \Roave\NoLeaks\PHPUnit\EmptyBaselineMemoryUsageTest */
final class EmptyBaselineMemoryUsageTest extends TestCase
{
    public function testDoesNotProduceMemoryLeaks() : void
    {
        $test = new Baseline();

        $memoryUsage = 1;

        // intentional immediate value override - we're offsetting memory usage here, on purpose
        $memoryUsage = memory_get_usage();

        $test->emptyTest();

        self::assertSame($memoryUsage, memory_get_usage());
        self::assertSame(1, $test->getNumAssertions());
    }
}
