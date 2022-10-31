<?php

declare(strict_types=1);

namespace Roave\NoLeaks\PHPUnit;

use function array_filter;
use function array_map;
use function array_slice;
use function assert;
use function count;
use function min;

/**
 * @internal this class is not to be used outside this package
 *
 * Instances of this class represent the memory leakage deriving from a single test run.
 * Such leakages are inevitable, since the test framework needs to track executed test,
 * statistics, error messages, etc.
 */
final class MeasuredTestRunMemoryLeak
{
    /** @param non-empty-list<int> $memoryUsages */
    private function __construct(private readonly array $memoryUsages)
    {
    }

    /**
     * @param list<int> $preRunMemoryUsages
     * @param list<int> $postRunMemoryUsages
     */
    public static function fromTestMemoryUsages(
        array $preRunMemoryUsages,
        array $postRunMemoryUsages
    ): self {
        $snapshotsCount = min(count($preRunMemoryUsages), count($postRunMemoryUsages));

        $results = array_map(static function (int $beforeRun, int $afterRun): int {
            return $afterRun - $beforeRun;
        }, array_slice($preRunMemoryUsages, 0, $snapshotsCount), array_slice($postRunMemoryUsages, 0, $snapshotsCount));

        assert($results !== []);

        return new self($results);
    }

    public function leaksMemory(MeasuredBaselineTestMemoryLeak $baseline): bool
    {
        // If at least one of the runs does not leak memory, then the leak does not come from inside the test,
        // but from the test runner noise. This is naive, but also an acceptable threshold for most test suites
        return array_filter(array_map(
            static function (int $memoryUsage) use ($baseline): bool {
                    return ! $baseline->lessThan($memoryUsage);
            },
            $this->memoryUsages
        )) === [];
    }
}
