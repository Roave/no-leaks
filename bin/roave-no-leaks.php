<?php

declare(strict_types=1);

namespace Roave\NoLeaks\CLI;

use Roave\NoLeaks\PHPUnit\PHPUnitCommand;

(function () {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    } else {
        require_once __DIR__ . '/../../../autoload.php';
    }

    $_SERVER['argv'][] = '--repeat=3';

    PHPUnitCommand::main();
})();
