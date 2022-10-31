<?php

declare(strict_types=1);

namespace Roave\NoLeaks\PHPUnit;

use Exception;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestListenerDefaultImplementation;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\AfterSuccessfulTestHook;
use PHPUnit\Runner\BeforeTestHook;

use function array_combine;
use function array_filter;
use function array_intersect_key;
use function array_key_exists;
use function array_keys;
use function array_map;
use function gc_collect_cycles;
use function implode;
use function memory_get_usage;
use function sprintf;

use const PHP_VERSION_ID;

/**
 * Note: we need to implement TestListener, because the hook API is not allowing
 *       us to interact with test suite instances. This means that the entire package
 *       may need a rewrite when PHPUnit 9.0 is out, but YOLO.
 */
final class CollectTestExecutionMemoryFootprints implements
    BeforeTestHook,
    AfterSuccessfulTestHook,
    AfterLastTestHook,
    TestListener
{
    use TestListenerDefaultImplementation;

    /** @var array<string, array<int, int>> */
    private array $preTestMemoryUsages = [];

    /** @var array<string, array<int, int>> */
    private array $postTestMemoryUsages = [];

    public function startTestSuite(TestSuite $suite): void
    {
        $suite->addTest(new EmptyBaselineMemoryUsageTest(EmptyBaselineMemoryUsageTest::TEST_METHOD));
    }

    public function executeBeforeTest(string $test): void
    {
        gc_collect_cycles();
        if (PHP_VERSION_ID < 80100) {
            gc_collect_cycles();
        }

        $this->preTestMemoryUsages[$test][] = memory_get_usage();
    }

    public function executeAfterSuccessfulTest(string $test, float $time): void
    {
        gc_collect_cycles();
        if (PHP_VERSION_ID < 80100) {
            gc_collect_cycles();
        }

        $this->postTestMemoryUsages[$test][] = memory_get_usage();
    }

    public function executeAfterLastTest(): void
    {
        if (
            ! (
            array_key_exists(EmptyBaselineMemoryUsageTest::class . '::' . EmptyBaselineMemoryUsageTest::TEST_METHOD, $this->preTestMemoryUsages)
            && array_key_exists(EmptyBaselineMemoryUsageTest::class . '::' . EmptyBaselineMemoryUsageTest::TEST_METHOD, $this->postTestMemoryUsages)
            )
        ) {
            throw new Exception('Could not find baseline test: impossible to determine PHPUnit base memory overhead');
        }

        $baselineMemoryUsage = MeasuredBaselineTestMemoryLeak::fromBaselineTestMemoryUsages(
            $this->preTestMemoryUsages[EmptyBaselineMemoryUsageTest::class . '::' . EmptyBaselineMemoryUsageTest::TEST_METHOD],
            $this->postTestMemoryUsages[EmptyBaselineMemoryUsageTest::class . '::' . EmptyBaselineMemoryUsageTest::TEST_METHOD]
        );

        unset(
            $this->preTestMemoryUsages[EmptyBaselineMemoryUsageTest::class . '::' . EmptyBaselineMemoryUsageTest::TEST_METHOD],
            $this->postTestMemoryUsages[EmptyBaselineMemoryUsageTest::class . '::' . EmptyBaselineMemoryUsageTest::TEST_METHOD]
        );

        $successfullyExecutedTests = array_intersect_key($this->preTestMemoryUsages, $this->postTestMemoryUsages);

        $memoryUsages = array_combine(
            array_keys($successfullyExecutedTests),
            array_map(
                [MeasuredTestRunMemoryLeak::class, 'fromTestMemoryUsages'],
                $successfullyExecutedTests,
                $this->postTestMemoryUsages
            )
        );

        $leaks = array_filter(array_map(static function (MeasuredTestRunMemoryLeak $profile) use ($baselineMemoryUsage): bool {
            return $profile->leaksMemory($baselineMemoryUsage);
        }, $memoryUsages));

        if ($leaks !== []) {
            throw new Exception(sprintf(
                "The following test produced memory leaks:\n * %s\n",
                implode("\n * ", array_keys($leaks))
            ));
        }
    }
}
