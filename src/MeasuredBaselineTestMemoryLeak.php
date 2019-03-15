<?php

declare(strict_types=1);

namespace Roave\NoLeaks\PHPUnit;

/**
 * @internal this class is not to be used outside this package
 *
 *
 * Instances of this class represent the memory leakage deriving from a single empty
 * test case, such as {@see \Roave\NoLeaks\PHPUnit\EmptyBaselineMemoryUsageTest}.
 * A baseline of consumed memory per test is necessary to distinguish test framework
 * leaks from test/source leaks.
 */
final class MeasuredBaselineTestMemoryLeak
{
    /**
     * @var array<int, int> the amount of memory consumed for running the baseline test, usually caused by collecting
     *                      stats about it or similar state, retained by the test runner and its plugins
     */
    private $baselineTestRunsMemoryLeaks;

    private function __construct(int $firstMemoryUsage, int ...$furtherMemoryUsages)
    {
        $this->baselineTestRunsMemoryLeaks = array_merge([$firstMemoryUsage], $furtherMemoryUsages);
    }

    /**
     * @param array<int, int> $preBaselineTestMemoryUsages
     * @param array<int, int> $postBaselineTestMemoryUsages
     */
    public static function fromBaselineTestMemoryUsages(
        array $preBaselineTestMemoryUsages,
        array $postBaselineTestMemoryUsages
    ) : self {
        if (count($preBaselineTestMemoryUsages) !== count($postBaselineTestMemoryUsages)) {
            throw new \Exception('Pre- and post- baseline test run collected memory usages don\'t match in number');
        }

        if (count($preBaselineTestMemoryUsages) < 3) {
            throw new \Exception(sprintf(
                'At least 3 baseline test run memory profiles are required, %d given',
                count($preBaselineTestMemoryUsages)
            ));
        }

        $memoryUsages = array_values(array_map(function (int $beforeRun, int $afterRun) : int {
            $memoryUsage = $afterRun - $beforeRun;

            if ($memoryUsage < 0) {
                throw new \Exception(sprintf(
                    'Baseline memory usage of %d detected: invalid negative memory usage',
                    $memoryUsage
                ));
            }

            return $memoryUsage;
        }, $preBaselineTestMemoryUsages, $postBaselineTestMemoryUsages));

        // Note: profile 0 is discarded, as it may contain autoloading and other static test suite initialisation state
        $relevantMemoryUsages = array_slice($memoryUsages, 1);

        if ([] === array_filter(array_count_values($relevantMemoryUsages), function (int $count) : bool {
            return $count > 1;
        })) {
            // @TODO good enough for detecting standard deviation for now, I guess? :|
            throw new \Exception(sprintf(
                'Very inconsistent baseline memory usage profiles: could not find two equal values in profile %s',
                json_encode($memoryUsages, \JSON_THROW_ON_ERROR)
            ));
        }

        return new self(...$relevantMemoryUsages);
    }

    public function lessThan(int $testRunMemoryLeak) : bool
    {
        return (array_sum($this->baselineTestRunsMemoryLeaks) / count($this->baselineTestRunsMemoryLeaks)) < $testRunMemoryLeak;
    }
}
