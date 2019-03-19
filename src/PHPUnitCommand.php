<?php

declare(strict_types=1);

namespace Roave\NoLeaks\PHPUnit;

use PHPUnit\TextUI\Command;
use PHPUnit\TextUI\TestRunner;
use function array_merge;

final class PHPUnitCommand extends Command
{
    public static function main(bool $exit = true) : int
    {
        $command = new static();
        return $command->run($_SERVER['argv'], $exit);
    }

    protected function handleArguments(array $argv) : void
    {
        parent::handleArguments($argv);
        if (! $this->isAutoConfigureEnabled()) {
            return;
        }
        $this->arguments['listeners'] = array_merge(
            [new CollectTestExecutionMemoryFootprints()],
            $this->arguments['listeners'] ?? []
        );
    }

    protected function createRunner() : TestRunner
    {
        $testRunner = new TestRunner($this->arguments['loader']);
        if ($this->isAutoConfigureEnabled()) {
            $testRunner->addExtension(new CollectTestExecutionMemoryFootprints());
        }
        return $testRunner;
    }

    protected function isAutoConfigureEnabled() : bool
    {
        return isset($_ENV['REGISTER_NO_LEAKS']) && $_ENV['REGISTER_NO_LEAKS'] === '1';
    }
}
