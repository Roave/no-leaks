<?php

declare(strict_types=1);

namespace RoaveUnitTest\NoLeaks\PHPUnit;

use PHPUnit\Framework\TestCase;
use Roave\NoLeaks\PHPUnit\MeasuredBaselineTestMemoryLeak;
use Roave\NoLeaks\PHPUnit\MeasuredTestRunMemoryLeak;

/**
 * @uses \Roave\NoLeaks\PHPUnit\MeasuredBaselineTestMemoryLeak
 *
 * @covers \Roave\NoLeaks\PHPUnit\MeasuredTestRunMemoryLeak
 */
final class MeasuredTestRunMemoryLeakTest extends TestCase
{
    public function testMemoryLeakNotDetectedIFAtLeastOneRunIsSameAsBaselineRunLeak(): void
    {
        $averageBaselineOf10 = MeasuredBaselineTestMemoryLeak::fromBaselineTestMemoryUsages(
            [100, 10, 20, 30],
            [200, 20, 30, 40],
        );

        $measuredTestLeak = MeasuredTestRunMemoryLeak::fromTestMemoryUsages(
            [1000, 100, 200, 300],
            [2000, 110, 220, 330],
        );

        self::assertFalse($measuredTestLeak->leaksMemory($averageBaselineOf10));
    }

    public function testMemoryLeakNotDetectedIfAtLeastOneRunIsBelowBaselineRunLeak(): void
    {
        $averageBaselineOf10 = MeasuredBaselineTestMemoryLeak::fromBaselineTestMemoryUsages(
            [100, 10, 20, 30],
            [200, 20, 30, 40],
        );

        $measuredTestLeak = MeasuredTestRunMemoryLeak::fromTestMemoryUsages(
            [1000, 100, 200, 300],
            [2000, 105, 220, 330],
        );

        self::assertFalse($measuredTestLeak->leaksMemory($averageBaselineOf10));
    }

    public function testMemoryLeakDetectedIfAllRunsAreHigherThanBaselineRunLeak(): void
    {
        $averageBaselineOf10 = MeasuredBaselineTestMemoryLeak::fromBaselineTestMemoryUsages(
            [100, 10, 20, 30],
            [200, 20, 30, 40],
        );

        $measuredTestLeak = MeasuredTestRunMemoryLeak::fromTestMemoryUsages(
            [1000, 100, 200, 300],
            [2000, 111, 220, 330],
        );

        self::assertTrue($measuredTestLeak->leaksMemory($averageBaselineOf10));
    }

    public function testMemoryLeakNotDetectedIfAllRunsAreZero(): void
    {
        $averageBaselineOf10 = MeasuredBaselineTestMemoryLeak::fromBaselineTestMemoryUsages(
            [100, 10, 20, 30],
            [200, 20, 30, 40],
        );

        $measuredTestLeak = MeasuredTestRunMemoryLeak::fromTestMemoryUsages(
            [0, 0, 0],
            [0, 0, 0],
        );

        self::assertFalse($measuredTestLeak->leaksMemory($averageBaselineOf10));
    }

    public function testMemoryLeakDetectionAroundZeroBaseline(): void
    {
        $averageBaselineOf0 = MeasuredBaselineTestMemoryLeak::fromBaselineTestMemoryUsages(
            [100, 10, 20, 30],
            [200, 10, 20, 30],
        );

        self::assertFalse(MeasuredTestRunMemoryLeak::fromTestMemoryUsages([1], [0])->leaksMemory($averageBaselineOf0));
        self::assertFalse(MeasuredTestRunMemoryLeak::fromTestMemoryUsages([0], [0])->leaksMemory($averageBaselineOf0));
        self::assertTrue(MeasuredTestRunMemoryLeak::fromTestMemoryUsages([0], [1])->leaksMemory($averageBaselineOf0));
    }

    public function testWillOnlyConsiderPreRunMemorySnapshotsForWhichAPostRunSnapshotExists(): void
    {
        $averageBaselineOf10 = MeasuredBaselineTestMemoryLeak::fromBaselineTestMemoryUsages(
            [100, 10, 20, 30],
            [200, 20, 30, 40],
        );

        $measuredTestLeak = MeasuredTestRunMemoryLeak::fromTestMemoryUsages(
            [1000, 200, 300, 400],
            [2000, 220, 330],
        );

        self::assertTrue($measuredTestLeak->leaksMemory($averageBaselineOf10));
    }

    public function testWillOnlyConsiderPostRunMemorySnapshotsForWhichAPreRunSnapshotExists(): void
    {
        $averageBaselineOf10 = MeasuredBaselineTestMemoryLeak::fromBaselineTestMemoryUsages(
            [100, 10, 20, 30],
            [200, 20, 30, 40],
        );

        $measuredTestLeak = MeasuredTestRunMemoryLeak::fromTestMemoryUsages(
            [1000, 200, 300],
            [2000, 220, 330, 400],
        );

        self::assertTrue($measuredTestLeak->leaksMemory($averageBaselineOf10));
    }
}
