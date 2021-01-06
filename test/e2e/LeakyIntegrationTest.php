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
    /** @test */
    public function doesNotLeakMemory() : void
    {
        $this->addToAssertionCount(1);
    }

    /** @test */
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
        LeakyStaticObject::leak(null);

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function doesLeakAMock() : void
    {
        LeakyStaticObject::leak($this->createMock(stdClass::class));

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function doesLeakOneObject() : void
    {
        LeakyStaticObject::leak(new class {
        });

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function doesLeakTwoObjects() : void
    {
        LeakyStaticObject::leak(new class {
        });
        LeakyStaticObject::leak(new class {
        });

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function doesLeakTestItself() : void
    {
        LeakyStaticObject::leak($this);

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
        LeakyStaticObject::leak(str_repeat('a', 100000));
        LeakyStaticObject::leak(str_repeat('a', 100000));
        LeakyStaticObject::leak(str_repeat('a', 100000));

        $this->addToAssertionCount(1);
    }
}
