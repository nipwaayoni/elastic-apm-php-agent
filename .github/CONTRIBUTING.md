# Contributing

This document is a work in progress. Check back for changes.

## Code of Conduct

Please read and follow our [Code of Conduct](../CODE_OF_CONDUCT.md).

## Basics

1. Open an [issue](https://github.com/nipwaayoni/elastic-apm-php-agent/issues).
2. Fork the project, make and test your changes.
3. Open a Pull Request for review.

## Tests

Please try to use test driven development. At a minimum, update and add tests as appropriate. Tests can be run with a `composer` script:

```bash
composer ci:tests
```

The workflow enforces passing tests, so make sure to check.

## Code Style

We prefer the [PSR-12](https://www.php-fig.org/psr/psr-12/) code style format for this project. The workflow executes a `php-cs-fixer` script to check the code style and will fail if violations are found. You can run the check yourself with `composer`:

```bash
composer ci:fixer
```

You can also have `php-cs-fixer` apply fixes for you using:

```bash
composer fix
```

The code style applies to the `src` and `tests` directories. 

As of this writing, the PHP-CS-Fixer project has not implemented a bundled PSR-12 ruleset. We're using  modified ruleset which mostly mimics PSR-12 for now based on [this issue](https://github.com/FriendsOfPHP/PHP-CS-Fixer/issues/4502). The configuration can be found at:

```
config/php-cs-fixer.php
```
