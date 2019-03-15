<?php

declare(strict_types=1);

namespace Roave\NoLeaks\PHPUnit;

/**
 * @internal this class is not to be used outside this package
 *
 * Instances of this class represent the memory leakage deriving from a single test run.
 * Such leakages are inevitable, since the test framework needs to track executed test,
 * statistics, error messages, etc.
 */
final class MeasuredTestRunMemoryLeak
{
    /** @var array<int, int> positive integers, representing used memory */
    private $memoryUsages;

    private function __construct(int $firstMemoryUsage, int ...$furtherMemoryUsages)
    {
        $this->memoryUsages = array_merge([$firstMemoryUsage], $furtherMemoryUsages);
    }

    /**
     * @param array<int, int> $preRunMemoryUsages
     * @param array<int, int> $postRunMemoryUsages
     */
    public static function fromTestMemoryUsages(
        array $preRunMemoryUsages,
        array $postRunMemoryUsages
    ) : self {
        $snapshotsCount = min(count($preRunMemoryUsages), count($postRunMemoryUsages));

        return new self(...array_values(array_map(function (int $beforeRun, int $afterRun) : int {
            $memoryUsage = $afterRun - $beforeRun;

            if ($memoryUsage < 0) {
                throw new \Exception(sprintf('Baseline memory usage of %d detected: invalid negative memory usage', $memoryUsage));
            }

            return $memoryUsage;
        }, array_slice($preRunMemoryUsages, 0, $snapshotsCount), array_slice($postRunMemoryUsages, 0, $snapshotsCount))));
    }

    /**
     * @TODO this implementation is very naïve. We can do more, such as excluding items outside standard deviation,
     *       or handling of min/max memory usages in collected test results
     */
    public function leaksMemory(MeasuredBaselineTestMemoryLeak $baseline) : bool
    {
        // If at least one of the runs does not leak memory, then the leak does not come from inside the test,
        // but from the test runner noise. This is naive, but also an acceptable threshold for most test suites
        return array_filter(array_map(
                function (int $memoryUsage) use ($baseline) : bool {
                    return ! $baseline->lessThan($memoryUsage);
                },
                $this->memoryUsages
            )) === [];
    }
}
