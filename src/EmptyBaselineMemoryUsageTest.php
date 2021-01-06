<?php

declare(strict_types=1);

namespace Roave\NoLeaks\PHPUnit;

use PHPUnit\Framework\TestCase;

/**
 * @internal this class is not to be used outside this package
 *
 * @coversNothing
 */
final class EmptyBaselineMemoryUsageTest extends TestCase
{
    public const TEST_METHOD      = 'testMemoryBaselineWithEmptyTestBody';
    private const ASSERTION_COUNT = 1;

    /**
     * An empty baseline test that will be used to measure pre- and post-run
     * memory usage, to be compared with the rest of the test suite runs.
     *
     * Tests that deviate from this baseline pre/post memory usage are most
     * likely leaking.
     */
    public function testMemoryBaselineWithEmptyTestBody(): void
    {
        $this->addToAssertionCount(self::ASSERTION_COUNT);
    }
}
