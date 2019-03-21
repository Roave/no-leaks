<?php

declare(strict_types=1);

namespace Roave\NoLeaks\CLI;

use PHPUnit\TextUI\Command;
use PHPUnit\TextUI\TestRunner;
use Roave\NoLeaks\PHPUnit\CollectTestExecutionMemoryFootprints;

(function () {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    } else {
        require_once __DIR__ . '/../../../autoload.php';
    }

    (new class extends Command
    {
        /** @var CollectTestExecutionMemoryFootprints */
        private $collector;

        public function __construct()
        {
            $this->collector = new CollectTestExecutionMemoryFootprints();
        }

        protected function handleArguments(array $argv) : void
        {
            parent::handleArguments($argv);

            $this->arguments['listeners'] = array_merge(
                $this->arguments['listeners'] ?? [],
                [$this->collector]
            );
        }

        protected function createRunner() : TestRunner
        {
            $runner = parent::createRunner();

            $runner->addExtension($this->collector);

            return $runner;
        }
    })->run(array_merge($_SERVER['argv'], ['--repeat=3']), true);
})();
