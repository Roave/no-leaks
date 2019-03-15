<?php

declare(strict_types=1);

namespace RoaveE2ETest\NoLeaks\PHPUnit;

use PHPUnit\Framework\TestCase;
use stdClass;
use function spl_autoload_register;
use function str_repeat;

/** @coversNothing this is an integration test that spans the entirety of the library */
final class LeakyIntegrationTest extends TestCase
{
    /** @var array<int, mixed> */
    private static $memoryLeakingStupidMistake = [];

    /** @test */
    public function doesNotLeakMemory() : void
    {
        $this->addToAssertionCount(1);
    }

    public function failingTestShouldNotBeCheckedForLeaks() : void
    {
        self::fail();
    }

    /** @test */
    public function doesNotLeakMemoryIfCyclesAreGarbageCollected() : void
    {
        $a = new stdClass();
        $b = new stdClass();
        $c = new stdClass();

        $a->b = $b;
        $b->c = $c;
        $c->a = $a;

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function doesLeakATinyAmountOfMemory() : void
    {
        self::$memoryLeakingStupidMistake[] = null;

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function doesLeakAMock() : void
    {
        self::$memoryLeakingStupidMistake[] = $this->createMock(stdClass::class);

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function doesLeakOneObject() : void
    {
        self::$memoryLeakingStupidMistake[] = new class {
        };

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function doesLeakTwoObjects() : void
    {
        self::$memoryLeakingStupidMistake[] = new class {
        };
        self::$memoryLeakingStupidMistake[] = new class {
        };

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function doesLeakTestItself() : void
    {
        self::$memoryLeakingStupidMistake[] = $this;

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function doesLeakAnAutoloader() : void
    {
        spl_autoload_register(static function (string $className) : bool {
            return false;
        });

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function doesLeakAStaticAutoloader() : void
    {
        spl_autoload_register(static function (string $className) : bool {
            return false;
        });

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function doesLeakLotsAndLotsOfMemory() : void
    {
        self::$memoryLeakingStupidMistake[] = str_repeat('a', 10000);

        $this->addToAssertionCount(1);
    }
}
