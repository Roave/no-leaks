<?php

declare(strict_types=1);

namespace RoaveUnitTest\NoLeaks\PHPUnit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use Roave\NoLeaks\PHPUnit\CollectTestExecutionMemoryFootprints;
use Roave\NoLeaks\PHPUnit\EmptyBaselineMemoryUsageTest as Baseline;
use stdClass;
use function array_map;
use function array_merge;
use function range;

/**
 * @uses   \Roave\NoLeaks\PHPUnit\EmptyBaselineMemoryUsageTest
 * @uses   \Roave\NoLeaks\PHPUnit\MeasuredBaselineTestMemoryLeak
 * @uses   \Roave\NoLeaks\PHPUnit\MeasuredTestRunMemoryLeak
 *
 * @covers \Roave\NoLeaks\PHPUnit\CollectTestExecutionMemoryFootprints
 */
final class CollectTestExecutionMemoryFootprintsTest extends TestCase
{
    public function testWillCollectFootprints() : void
    {
        $collector = new CollectTestExecutionMemoryFootprints();

        $mocks = [];

        $mocks[] = $this->createMock(stdClass::class);

        $collector->executeBeforeTest('nonLeakyTest');
        $mocks[] = $this->createMock(stdClass::class);
        $collector->executeAfterSuccessfulTest('nonLeakyTest', 0.0);
        $collector->executeBeforeTest('nonLeakyTest');
        $mocks[] = $this->createMock(stdClass::class);
        $collector->executeAfterSuccessfulTest('nonLeakyTest', 0.0);
        $collector->executeBeforeTest('nonLeakyTest');
        $mocks[] = $this->createMock(stdClass::class);
        $collector->executeAfterSuccessfulTest('nonLeakyTest', 0.0);

        $collector->executeBeforeTest('memoryEatingTest');
        $mocks = $this->createMocks(10, $mocks);
        $collector->executeAfterSuccessfulTest('memoryEatingTest', 0.0);
        $collector->executeBeforeTest('memoryEatingTest');
        $mocks = $this->createMocks(10, $mocks);
        $collector->executeAfterSuccessfulTest('memoryEatingTest', 0.0);
        $collector->executeBeforeTest('memoryEatingTest');
        $mocks = $this->createMocks(10, $mocks);
        $collector->executeAfterSuccessfulTest('memoryEatingTest', 0.0);

        $collector->executeBeforeTest('failingTest');
        $mocks[] = $this->createMock(stdClass::class);
        $mocks[] = $this->createMock(stdClass::class);
        $collector->executeBeforeTest('failingTest');
        $mocks[] = $this->createMock(stdClass::class);
        $mocks[] = $this->createMock(stdClass::class);
        $collector->executeBeforeTest('failingTest');
        $mocks[] = $this->createMock(stdClass::class);
        $mocks[] = $this->createMock(stdClass::class);

        $this->collectBaseline($collector);

        $this->expectExceptionMessage(<<<'MESSAGE'
The following test produced memory leaks:
 * memoryEatingTest
MESSAGE
        );

        $collector->executeAfterLastTest();

        $this->consumeMocks(...$mocks);
    }

    public function testGarbageCollectedMemoryCyclesAreNotReportedAsFailures() : void
    {
        $collector = new CollectTestExecutionMemoryFootprints();

        $mocks = [$this->createMock(stdClass::class)];

        $this->collectBaseline($collector);

        $collector->executeBeforeTest('memoryEatingTest');
        $mocks = $this->createMocks(10, $mocks);
        $collector->executeAfterSuccessfulTest('memoryEatingTest', 0.0);
        $collector->executeBeforeTest('memoryEatingTest');
        $mocks = $this->createMocks(10, $mocks);
        $collector->executeAfterSuccessfulTest('memoryEatingTest', 0.0);
        $collector->executeBeforeTest('memoryEatingTest');
        $mocks = $this->createMocks(10, $mocks);
        $collector->executeAfterSuccessfulTest('memoryEatingTest', 0.0);

        // Create multiple GC'd cycles to ensure that measured memory at the beginning and end of a test
        // are consistent
        $this->createGarbageCollectableCycle();
        $this->createGarbageCollectableCycle();
        $this->createGarbageCollectableCycle();
        $this->createGarbageCollectableCycle();
        $this->createGarbageCollectableCycle();
        $this->createGarbageCollectableCycle();
        $this->createGarbageCollectableCycle();
        $collector->executeBeforeTest('forcefullyCollectedNonLeakingCycleTest');
        $this->createGarbageCollectableCycle();
        $this->createGarbageCollectableCycle();
        $this->createGarbageCollectableCycle();
        $collector->executeAfterSuccessfulTest('forcefullyCollectedNonLeakingCycleTest', 0.0);
        $collector->executeBeforeTest('forcefullyCollectedNonLeakingCycleTest');
        $this->createGarbageCollectableCycle();
        $this->createGarbageCollectableCycle();
        $this->createGarbageCollectableCycle();
        $collector->executeAfterSuccessfulTest('forcefullyCollectedNonLeakingCycleTest', 0.0);
        $collector->executeBeforeTest('forcefullyCollectedNonLeakingCycleTest');
        $this->createGarbageCollectableCycle();
        $this->createGarbageCollectableCycle();
        $this->createGarbageCollectableCycle();
        $collector->executeAfterSuccessfulTest('forcefullyCollectedNonLeakingCycleTest', 0.0);

        $this->expectExceptionMessage(<<<'MESSAGE'
The following test produced memory leaks:
 * memoryEatingTest
MESSAGE
        );

        $collector->executeAfterLastTest();

        $this->consumeMocks(...$mocks);
    }

    public function testWillFailIfBaselineTestCouldNotBeRun() : void
    {
        $this->expectExceptionMessage(
            'Could not find baseline test: impossible to determine PHPUnit base memory overhead'
        );

        (new CollectTestExecutionMemoryFootprints())
            ->executeAfterLastTest();
    }

    public function testWillFailIfBaselineTestCouldNotBeRunSuccessfully() : void
    {
        $collector = new CollectTestExecutionMemoryFootprints();

        $collector->executeBeforeTest(Baseline::class . '::' . Baseline::TEST_METHOD);

        $this->expectExceptionMessage(
            'Could not find baseline test: impossible to determine PHPUnit base memory overhead'
        );

        $collector->executeAfterLastTest();
    }

    private function createGarbageCollectableCycle() : void
    {
        $a = new stdClass();
        $b = new stdClass();

        $a->b = $b;
        $b->a = $a;
    }

    private function consumeMocks(stdClass ...$mocks) : void
    {
    }

    public function testWillRegisterBaselineTestInTestSuite() : void
    {
        $testSuite = $this->createMock(TestSuite::class);

        $testSuite
            ->expects(self::once())
            ->method('addTest')
            ->with(self::equalTo(new Baseline('emptyTest')));

        (new CollectTestExecutionMemoryFootprints())->startTestSuite($testSuite);
    }

    private function collectBaseline(CollectTestExecutionMemoryFootprints $collector) : void
    {
        $mocks = [$this->createMock(stdClass::class)];

        foreach (range(1, 10) as $index) {
            $collector->executeBeforeTest(Baseline::class . '::' . Baseline::TEST_METHOD);

            $mocks[$index] = $this->createMock(stdClass::class);

            $collector->executeAfterSuccessfulTest(Baseline::class . '::' . Baseline::TEST_METHOD, 0.0);
        }

        $this->consumeMocks(...$mocks);
    }

    /**
     * @param stdClass[] $mocks
     *
     * @return stdClass[]
     */
    private function createMocks(int $amount, array $mocks) : array
    {
        return array_merge($mocks, array_map(function (int $index) : stdClass {
            return $this->createMock(stdClass::class);
        }, range(1, $amount)));
    }
}
