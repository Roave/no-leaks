<?php

declare(strict_types=1);

namespace RoaveUnitTest\NoLeaks\PHPUnit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use Roave\NoLeaks\PHPUnit\CollectTestExecutionMemoryFootprints;
use Roave\NoLeaks\PHPUnit\EmptyBaselineMemoryUsageTest as Baseline;
use stdClass;

/**
 * @covers \Roave\NoLeaks\PHPUnit\CollectTestExecutionMemoryFootprints
 *
 * @uses \Roave\NoLeaks\PHPUnit\EmptyBaselineMemoryUsageTest
 * @uses \Roave\NoLeaks\PHPUnit\MeasuredBaselineTestMemoryLeak
 * @uses \Roave\NoLeaks\PHPUnit\MeasuredTestRunMemoryLeak
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

        $collector->executeBeforeTest('doubleMemoryEatingTest');
        $mocks[] = $this->createMock(stdClass::class);
        $mocks[] = $this->createMock(stdClass::class);
        $collector->executeAfterSuccessfulTest('doubleMemoryEatingTest', 0.0);
        $collector->executeBeforeTest('doubleMemoryEatingTest');
        $mocks[] = $this->createMock(stdClass::class);
        $mocks[] = $this->createMock(stdClass::class);
        $collector->executeAfterSuccessfulTest('doubleMemoryEatingTest', 0.0);
        $collector->executeBeforeTest('doubleMemoryEatingTest');
        $mocks[] = $this->createMock(stdClass::class);
        $mocks[] = $this->createMock(stdClass::class);
        $collector->executeAfterSuccessfulTest('doubleMemoryEatingTest', 0.0);

        $collector->executeBeforeTest('failingTest');
        $mocks[] = $this->createMock(stdClass::class);
        $mocks[] = $this->createMock(stdClass::class);
        $collector->executeBeforeTest('failingTest');
        $mocks[] = $this->createMock(stdClass::class);
        $mocks[] = $this->createMock(stdClass::class);
        $collector->executeBeforeTest('failingTest');
        $mocks[] = $this->createMock(stdClass::class);
        $mocks[] = $this->createMock(stdClass::class);

        $collector->executeBeforeTest(Baseline::class . '::' . Baseline::TEST_METHOD);
        $mocks[] = $this->createMock(stdClass::class);
        $collector->executeAfterSuccessfulTest(Baseline::class . '::' . Baseline::TEST_METHOD, 0.0);
        $collector->executeBeforeTest(Baseline::class . '::' . Baseline::TEST_METHOD);
        $mocks[] = $this->createMock(stdClass::class);
        $collector->executeAfterSuccessfulTest(Baseline::class . '::' . Baseline::TEST_METHOD, 0.0);
        $collector->executeBeforeTest(Baseline::class . '::' . Baseline::TEST_METHOD);
        $mocks[] = $this->createMock(stdClass::class);
        $collector->executeAfterSuccessfulTest(Baseline::class . '::' . Baseline::TEST_METHOD, 0.0);

        $this->expectExceptionMessage(<<<'MESSAGE'
The following test produced memory leaks:
 * doubleMemoryEatingTest
MESSAGE
        );

        $collector->executeAfterLastTest();

        $this->consumeMocks(...$mocks);
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
}
