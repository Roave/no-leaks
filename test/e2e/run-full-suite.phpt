--TEST--
Verifies that known detectable memory leaks will be rendered as a test failure when running through an entire test suite
--FILE--
<?php

chdir(__DIR__);

$_SERVER['argv'][] = '--configuration=phpunit-mock-suite.xml';

require __DIR__ . '/../../bin/roave-no-leaks.php';

?>
--EXPECTF--
%aThe following test produced memory leaks:
 * RoaveE2ETest\NoLeaks\PHPUnit\LeakyIntegrationTest::doesLeakAMock
 * RoaveE2ETest\NoLeaks\PHPUnit\LeakyIntegrationTest::doesLeakTwoObjects
 * RoaveE2ETest\NoLeaks\PHPUnit\LeakyIntegrationTest::doesLeakAnAutoloader
 * RoaveE2ETest\NoLeaks\PHPUnit\LeakyIntegrationTest::doesLeakAStaticAutoloader
 * RoaveE2ETest\NoLeaks\PHPUnit\LeakyIntegrationTest::doesLeakLotsAndLotsOfMemory
