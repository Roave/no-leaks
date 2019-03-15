<?php

declare(strict_types=1);

namespace Roave\NoLeaks\PHPUnit;

use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestListener;
use PHPUnit\Framework\TestSuite;
use PHPUnit\Framework\Warning;
use PHPUnit\Runner\AfterLastTestHook;
use PHPUnit\Runner\AfterSuccessfulTestHook;
use PHPUnit\Runner\BeforeTestHook;

/**
 * Note: we need to implement TestListener, because the hook API is not allowing
 *       us to interact with test suite instances. This means that the entire package
 *       may need a rewrite when PHPUnit 9.0 is out, but YOLO.
 */
final class CollectTestExecutionMemoryFootprints
    implements BeforeTestHook,
    AfterSuccessfulTestHook,
    AfterLastTestHook,
    TestListener
{
    /** @var array<string, array<int, int>> */
    private $preTestMemoryUsages = [];

    /** @var array<string, array<int, int>> */
    private $postTestMemoryUsages = [];

    public function startTestSuite(TestSuite $suite) : void
    {
        $suite->addTest(new EmptyBaselineMemoryUsageTest(EmptyBaselineMemoryUsageTest::TEST_METHOD));
    }

    public function executeBeforeTest(string $test) : void
    {
        \gc_collect_cycles();

        $this->preTestMemoryUsages[$test][] = \memory_get_usage();
    }

    public function executeAfterSuccessfulTest(string $test, float $time) : void
    {
        \gc_collect_cycles();

        $this->postTestMemoryUsages[$test][] = \memory_get_usage();
    }

    public function executeAfterLastTest() : void
    {
        if (! (
            array_key_exists(EmptyBaselineMemoryUsageTest::class . '::' . EmptyBaselineMemoryUsageTest::TEST_METHOD, $this->preTestMemoryUsages)
            && array_key_exists(EmptyBaselineMemoryUsageTest::class . '::' . EmptyBaselineMemoryUsageTest::TEST_METHOD, $this->postTestMemoryUsages)
        )) {
            throw new \Exception('Could not find baseline test: impossible to determine PHPUnit base memory overhead');
        }

        $baselineMemoryUsage = MeasuredBaselineTestMemoryLeak::fromBaselineTestMemoryUsages(
            $this->preTestMemoryUsages[EmptyBaselineMemoryUsageTest::class . '::' . EmptyBaselineMemoryUsageTest::TEST_METHOD],
            $this->postTestMemoryUsages[EmptyBaselineMemoryUsageTest::class . '::' . EmptyBaselineMemoryUsageTest::TEST_METHOD]
        );

        unset(
            $this->preTestMemoryUsages[EmptyBaselineMemoryUsageTest::class . '::' . EmptyBaselineMemoryUsageTest::TEST_METHOD],
            $this->postTestMemoryUsages[EmptyBaselineMemoryUsageTest::class . '::' . EmptyBaselineMemoryUsageTest::TEST_METHOD]
        );

        $memoryUsages = array_combine(
            array_keys($this->preTestMemoryUsages),
            array_map(
                [MeasuredTestRunMemoryLeak::class, 'fromTestMemoryUsages'],
                $this->preTestMemoryUsages,
                $this->postTestMemoryUsages
            )
        );

        $leaks = array_filter(array_map(function (MeasuredTestRunMemoryLeak $profile) use ($baselineMemoryUsage) : bool {
            return $profile->leaksMemory($baselineMemoryUsage);
        }, $memoryUsages));

        if ([] !== $leaks) {
            throw new \Exception(sprintf(
                "The following test produced memory leaks:\n * %s\n",
                implode("\n * ", array_keys($leaks))
            ));
        }
    }

    // The following bits are just stubbing the implementation of the
    // deprecated {@see \PHPUnit\Framework\TestListener\TestListener} API

    public function addError(Test $test, \Throwable $t, float $time) : void
    {
    }

    public function addWarning(Test $test, Warning $e, float $time) : void
    {
    }

    public function addFailure(Test $test, AssertionFailedError $e, float $time) : void
    {
    }

    public function addIncompleteTest(Test $test, \Throwable $t, float $time) : void
    {
    }

    public function addRiskyTest(Test $test, \Throwable $t, float $time) : void
    {
    }

    public function addSkippedTest(Test $test, \Throwable $t, float $time) : void
    {
    }

    public function endTestSuite(TestSuite $suite) : void
    {
    }

    public function startTest(Test $test) : void
    {
    }

    public function endTest(Test $test, float $time) : void
    {
    }
}
