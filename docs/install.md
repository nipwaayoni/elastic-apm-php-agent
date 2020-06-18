# Installation

The recommended way to install the agent is through [Composer](http://getcomposer.org).

Run the following composer command:

```bash
composer require nipwaayoni/elastic-apm-php-agent
```

After installing, you need to require Composer's autoloader:

```php
require 'vendor/autoload.php';
```

## HTTP ClientInterface

This package uses, but does not provide, a [PSR-18 HTTP client interface](https://www.php-fig.org/psr/psr-18/) compatible implementation. Internally, the package uses [php-http/discovery](https://github.com/php-http/discovery) to find suitable PSR-17 factories and PSR-18 clients.

If the package cannot find suitable PSR-17 and PSR-18 components, it will throw an`\Http\Discovery\Exception\NotFoundException` exception.

If your project does not already include such an implementation, you may choose to require the following:

```bash
composer require http-interop/http-factory-guzzle php-http/guzzle6-adapter
```

This installs the `php-http/guzzle6-adapter` (as PSR-18 compatible client) and `http-interop/http-factory-guzzle` (as PSR-17 compatible factories) composer packages. Once installed, auto-discovery will find and use them. If you want, you can inject your own client and factories by implementing PSR-17 and PSR-18 interfaces and passing objects using the `AgentBuilder` class. (See the [agent example](examples/agent-init.md).)

Note that the PSR-18 space is still evolving and direct support without adapters could be available at any time.

See all available PSR-17 factories [here](https://packagist.org/providers/psr/http-factory-implementation).

See all available PSR-18 clients [here](https://packagist.org/providers/psr/http-client-implementation).

