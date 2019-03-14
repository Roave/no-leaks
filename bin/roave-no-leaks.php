<?php

declare(strict_types=1);

namespace Roave\NoLeaks\CLI;

use PHPUnit\TextUI\Command;

(function () {
    require_once __DIR__ . '/../vendor/autoload.php';

    $_SERVER['argv'][] = '--repeat=3';

    Command::main();
})();
