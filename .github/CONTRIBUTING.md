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

## Schema Validation

This package exists to push data to the APM service. The data emitted must adhere to the schema provided by the APM project. The APM projects makes the schema definition available for each release and we can use that to validate the data produced by this package.

### Implement Schema Validation

Your test class must extend `Nipwaayoni\Tests\SchemaTestCase` to perform schema validation. Implement the abstract methods as detailed in the associated PHPDoc blocks.

The `testProducesValidJson()` provides the minimum test expected for validation. You should add more tests to cover any possible conditions which may alter the object structure resulting from the JSON serialization.

### Add a New Schema Specification

We will use version 7.8 for this example.

1. Clone the [elastic/apm-server](https://github.com/elastic/apm-server) project
2. Check out the desired version branch: `7.8`
3. Create a new version directory in the project: `schema/apm-7.8/docs`
4. Copy the spec directory to the new spec directory: `cp -r /path/to/apm-server/docs/spec /path/to/elastic-apm-php-agent/schema/apm-7.8/docs/spec`

### Declare the New Version as Supported

The `Nipwaayoni\Tests\SchemaTestCase::SUPPORTED_SCHEMA_VERSIONS` is an array of officially supported versions. Add the new version to that array, then locate and update all implementations of the `Nipwaayoni\Tests\SchemaTestCase::schemaVersionDataProvider()` data provider method to include the new version. 

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

## Tools

Tools used during the development process should be installed using [phive](https://github.com/phar-io/phive) whenever possible rather than requiring them via `composer`. This reduces the chances of package conflicts during dependency resolution. The phar file should be copied to the `tools` directory at the root of the project. It's helpful to other developers if you add common execution commands as composer scripts. (See the phpunit and php-cs-fixer tools for examples.)

### PHPUnit and IDEs

IDEs, such as PhpStorm, index source files in order to provide class resolution and other helpful features. Since the source files for PHPUnit are not in the `vendor` directory, your IDE may not provide the normal class resolution for PHPUnit classes. Any good IDE should allow you to specify additional source directories. You can download the PHPUnit source to a location outside of the project and have your IDE include that as a source to restore the expected behavior. When tests are executed using `tools/phpunit`, the phpunit phar file will be loaded and the classes resolved through it.

PhpStorm is able to index the contents of a phar file directly. Unfortunately, it requires that the file have the `.phar` extension and phive currently installs tools without an extension. One easy solution is to create a symlink of `tools/phpunit` to `tools/phpunit.phar` which will allow PhpStorm to find and index the PHPUnit classes. (We have added `tools/*.phar` to `.gitignore` so those won't be committed.) MacOS aliases or Windows shortcuts should work for this as well, though we have not verified that.
