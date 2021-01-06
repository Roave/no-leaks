<?php

declare(strict_types=1);

namespace Roave\NoLeaks\PHPUnit;

use Exception;

use function array_count_values;
use function array_filter;
use function array_map;
use function array_merge;
use function array_slice;
use function array_sum;
use function count;
use function json_encode;
use function sprintf;

use const JSON_THROW_ON_ERROR;

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
    private array $baselineTestRunsMemoryLeaks;

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
    ): self {
        if (count($preBaselineTestMemoryUsages) !== count($postBaselineTestMemoryUsages)) {
            throw new Exception('Pre- and post- baseline test run collected memory usages don\'t match in number');
        }

        $memoryUsages = array_map(static function (int $beforeRun, int $afterRun): int {
            return $afterRun - $beforeRun;
        }, $preBaselineTestMemoryUsages, $postBaselineTestMemoryUsages);

        // Note: profile 0 is discarded, as it may contain autoloading and other static test suite initialisation state
        $relevantMemoryUsages = array_slice($memoryUsages, 1);

        $nonNegativeMemoryUsages = array_filter(
            $relevantMemoryUsages,
            static function (int $memoryUsage): bool {
                return $memoryUsage >= 0;
            }
        );

        if (count($nonNegativeMemoryUsages) < 2) {
            throw new Exception(sprintf(
                'At least 3 baseline test run memory profiles are required, %d given',
                count($nonNegativeMemoryUsages) + 1
            ));
        }

        if (
            array_filter(array_count_values($nonNegativeMemoryUsages), static function (int $count): bool {
                return $count > 1;
            }) === []
        ) {
            // @TODO good enough for detecting standard deviation for now, I guess? :|
            throw new Exception(sprintf(
                'Very inconsistent baseline memory usage profiles: could not find two equal values in profile %s',
                json_encode($memoryUsages, JSON_THROW_ON_ERROR)
            ));
        }

        return new self(...$nonNegativeMemoryUsages);
    }

    public function lessThan(int $testRunMemoryLeak): bool
    {
        return array_sum($this->baselineTestRunsMemoryLeaks) / count($this->baselineTestRunsMemoryLeaks) < $testRunMemoryLeak;
    }
}
