<?php

declare(strict_types=1);

namespace RoaveUnitTest\NoLeaks\PHPUnit;

use PHPUnit\Framework\TestCase;
use Roave\NoLeaks\PHPUnit\MeasuredBaselineTestMemoryLeak;

/** @covers \Roave\NoLeaks\PHPUnit\MeasuredBaselineTestMemoryLeak */
final class MeasuredBaselineTestMemoryLeakTest extends TestCase
{
    public function testDetectsAverageMemoryLeakThresholdByExcludingFirstProfile(): void
    {
        $measured = MeasuredBaselineTestMemoryLeak::fromBaselineTestMemoryUsages(
            [100, 40, 50, 60, 70, 80],
            [200, 40, 60, 80, 100, 110]
        );

        self::assertTrue($measured->lessThan(19));
        self::assertFalse($measured->lessThan(18));
        self::assertFalse($measured->lessThan(17));
    }

    public function testRejectsHighlyInconsistentProfiles(): void
    {
        $this->expectExceptionMessage(
            'Very inconsistent baseline memory usage profiles: '
            . 'could not find two equal values in profile [100,10,20,30,40]'
        );

        MeasuredBaselineTestMemoryLeak::fromBaselineTestMemoryUsages(
            [100, 50, 60, 70, 80],
            [200, 60, 80, 100, 120]
        );
    }

    public function testFiltersNegativeMemoryLeaks(): void
    {
        self::assertEquals(
            MeasuredBaselineTestMemoryLeak::fromBaselineTestMemoryUsages(
                [100, 51, 52, 53],
                [200, 51, 53, 54]
            ),
            MeasuredBaselineTestMemoryLeak::fromBaselineTestMemoryUsages(
                [100, 50, 51, 51, 53],
                [200, 49, 51, 52, 54]
            )
        );
    }

    public function testRejectsDataSetWithTooFewMemoryLeakProfiles(): void
    {
        $validMeasurement = MeasuredBaselineTestMemoryLeak::fromBaselineTestMemoryUsages(
            [100, 50, 60],
            [200, 50, 60]
        );

        self::assertTrue($validMeasurement->lessThan(1));
        self::assertFalse($validMeasurement->lessThan(0));

        $this->expectExceptionMessage('At least 3 baseline test run memory profiles are required, 2 given');

        MeasuredBaselineTestMemoryLeak::fromBaselineTestMemoryUsages(
            [100, 50],
            [200, 50]
        );
    }

    public function testRejectsDataSetWithTooFewValidMemoryLeakProfiles(): void
    {
        $this->expectExceptionMessage('At least 3 baseline test run memory profiles are required, 2 given');

        MeasuredBaselineTestMemoryLeak::fromBaselineTestMemoryUsages(
            [100, 50, 50],
            [200, 50, 49]
        );
    }

    public function testRejectsDataSetWithDifferentPreAndPostMemoryUsageSnapshots(): void
    {
        $this->expectExceptionMessage('Pre- and post- baseline test run collected memory usages don\'t match in number');

        MeasuredBaselineTestMemoryLeak::fromBaselineTestMemoryUsages(
            [100, 50, 52],
            [200, 49]
        );
    }
}
