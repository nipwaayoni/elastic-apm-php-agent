# Initialize the Agent

Since this package uses [PSR-18 HTTP client interface standard](https://www.php-fig.org/psr/psr-18/) 
we'll need to do some preparations before start.

This package uses [php-http/discovery](https://github.com/php-http/discovery) to find suitable PSR-17 factories 
and PSR-18 clients.

If `Agent` will not be able to find any suitable PSR-17 and PSR-18 components, it'll throw an
`\Http\Discovery\Exception\NotFoundException` exception.

We will install and use `php-http/guzzle6-adapter` (as PSR-18 compatible client) and `http-interop/http-factory-guzzle` 
(as PSR-17 compatible factories) composer packages in this example. Just require them with composer and auto-discovery 
will find them and use in `Agent` class. If you want, you can implement your own client and factories by implementing 
PSR-17 and PSR-18 interfaces. Also you can pass pre-initialized PSR-17 and PSR-18 classes into constructor of `Agent` 
class to ignore auto-discovery feature.

See all available PSR-17 factories [here](https://packagist.org/providers/psr/http-factory-implementation).

See all available PSR-18 clients [here](https://packagist.org/providers/psr/http-client-implementation).

See all configuration options [here](https://github.com/nipwaayoni/elastic-apm-php-agent/blob/master/docs/config.md).

## With minimal Config
```php
$agent = new \Nipwaayoni\Agent( [ 'appName' => 'demo' ] );
```

## With elaborate Config
When creating the agent, you can directly inject shared contexts such as user, tags and custom.
```php
$agent = new \Nipwaayoni\Agent( [ 'appName' => 'with-custom-context' ], [
  'user' => [
    'id'    => 12345,
    'email' => 'email@acme.com',
  ],
  'tags' => [
    // ... more key-values
  ],
  'custom' => [
    // ... more key-values
  ]
] );
```
