<?php

declare(strict_types=1);

namespace Roave\NoLeaks\CLI;

use PHPUnit\TextUI\Command;

(function () {
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
    } else {
        require_once __DIR__ . '/../../../vendor/autoload.php';
    }

    $_SERVER['argv'][] = '--repeat=3';

    Command::main();
})();
