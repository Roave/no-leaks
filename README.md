# roave/no-leaks

[![Latest Stable Version](https://poser.pugx.org/roave/no-leaks/v/stable.png)](https://packagist.org/packages/roave/no-leaks)

![roave/no-leaks](./logo/no-leaks.png)

This library is a [PHPUnit](https://github.com/sebastianbergmann/phpunit) plugin
that detects memory leaks in tested code or tests.
It utilizes `memory_get_usage()` to make sure the code beeing executed properly cleans up after itself.

## Installation

```sh
composer require --dev roave/no-leaks
```

## Usage

```sh
vendor/bin/roave-no-leaks
```

If any memory leaks are detected, you should see an output like
following:

```
Exception: The following test produced memory leaks:
 * My\Leaky\Test::testSomething
 * My\Leaky\Test::testSomethingElse
```

## Configuration and Parameters

[`vendor/bin/roave-no-leaks`](./bin/roave-no-leaks) supports all
configuration parameters and console parameters of PHPUnit.

## Known Issues

Please be aware that this is not a full substitute for PHPUnit:

 * the output format is to be improved
 * memory leak detection for scalar types and arrays is not reliable
 * can fail depending on xdebug/phpdbg/php-sapi changes 

## Professional Support

If you need help with setting up this library in your project,
you can contact us at team@roave.com for consulting/support.
