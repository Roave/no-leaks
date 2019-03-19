<?php

declare(strict_types=1);

namespace Roave\NoLeaks\PHPUnit;

use PHPUnit\TextUI\Command;
use PHPUnit\TextUI\TestRunner;
use function array_merge;

final class PHPUnitCommand extends Command
{
    public function run(array $argv, bool $exit = true) : int
    {
        // Should be improved
        $this->arguments['listeners'] = array_merge([new CollectTestExecutionMemoryFootprints()], $this->arguments['listeners'] ?? []);
        return parent::run($argv, $exit);
    }

    public static function main(bool $exit = true) : int
    {
        $command = new static();
        return $command->run($_SERVER['argv'], $exit);
    }

    protected function createRunner() : TestRunner
    {
        $testRunner =  new TestRunner($this->arguments['loader']);
        $testRunner->addExtension(new CollectTestExecutionMemoryFootprints());
        return $testRunner;
    }
}
