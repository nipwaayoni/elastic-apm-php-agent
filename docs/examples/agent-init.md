# Initialize the Agent

We will use `php-http/guzzle6-adapter` and `http-interop/http-factory-guzzle` packages in this example.
Just require them using composer.

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
