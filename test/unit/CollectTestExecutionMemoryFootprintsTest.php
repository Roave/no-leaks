<?php

declare(strict_types=1);

namespace RoaveUnitTest\NoLeaks\PHPUnit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use Roave\NoLeaks\PHPUnit\CollectTestExecutionMemoryFootprints;
use Roave\NoLeaks\PHPUnit\EmptyBaselineMemoryUsageTest as Baseline;

/** @covers \Roave\NoLeaks\PHPUnit\CollectTestExecutionMemoryFootprints */
final class CollectTestExecutionMemoryFootprintsTest extends TestCase
{
    public function testWillCollectFootprints() : void
    {
        $collector = new CollectTestExecutionMemoryFootprints();

        $collector->executeBeforeTest('test1');
        $mock1 = $this->createMock(\stdClass::class);
        $collector->executeAfterSuccessfulTest('test1', 0.0);
        $collector->executeBeforeTest('test1');
        $mock2 = $this->createMock(\stdClass::class);
        $collector->executeAfterSuccessfulTest('test1', 0.0);
        $collector->executeBeforeTest('test1');
        $mock3 = $this->createMock(\stdClass::class);
        $collector->executeAfterSuccessfulTest('test1', 0.0);

        // Following section eats double the memory
        $collector->executeBeforeTest('test2');
        $mock4 = $this->createMock(\stdClass::class);
        $mock5 = $this->createMock(\stdClass::class);
        $collector->executeAfterSuccessfulTest('test2', 0.0);
        $collector->executeBeforeTest('test2');
        $mock6 = $this->createMock(\stdClass::class);
        $mock7 = $this->createMock(\stdClass::class);
        $collector->executeAfterSuccessfulTest('test2', 0.0);
        $collector->executeBeforeTest('test2');
        $mock8 = $this->createMock(\stdClass::class);
        $mock9 = $this->createMock(\stdClass::class);
        $collector->executeAfterSuccessfulTest('test2', 0.0);

        $collector->executeBeforeTest(Baseline::class . '::' . Baseline::TEST_METHOD);
        $mock10 = $this->createMock(\stdClass::class);
        $collector->executeAfterSuccessfulTest(Baseline::class . '::' . Baseline::TEST_METHOD, 0.0);
        $collector->executeBeforeTest(Baseline::class . '::' . Baseline::TEST_METHOD);
        $mock11 = $this->createMock(\stdClass::class);
        $collector->executeAfterSuccessfulTest(Baseline::class . '::' . Baseline::TEST_METHOD, 0.0);
        $collector->executeBeforeTest(Baseline::class . '::' . Baseline::TEST_METHOD);
        $mock12 = $this->createMock(\stdClass::class);
        $collector->executeAfterSuccessfulTest(Baseline::class . '::' . Baseline::TEST_METHOD, 0.0);

        $this->expectExceptionMessage(<<<'MESSAGE'
The following test produced memory leaks:
 * test2
MESSAGE
        );

        $collector->executeAfterLastTest();

        $this->consumeMocks($mock1, $mock2, $mock3, $mock4, $mock5, $mock6, $mock7, $mock8, $mock9, $mock10, $mock11, $mock12);
    }

    /** @return array<int, object> */
    private function generateSomeMemoryTrash() : array
    {
        return array_map(function (int $number) : object {
            return (object) ['a' => $number];
        }, range(1, 100));
    }

    private function consumeMocks(\stdClass ...$mocks) : void
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
