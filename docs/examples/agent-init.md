# Initialize the Agent

Note: While it is still possible to directly create an `Agent` object, this example uses the preferred `AgentBuilder` class.

The `AgentBuilder` is used to set all `Agent` options and make the final `Agent` object.

```php
// Minimal required usage
$agent = (new \Nipwaayoni\AgentBuilder())->withConfig(new Config(['serviceName' => 'My Application']))->build();

// Setting more options
$builder = new \Nipwaayoni\AgentBuilder();
$builder->withConfig(new Nipwaayoni\Config(['serviceName' => 'My Application']));
$builder->withUserContextData([
        'id'    => 12345,
        'email' => 'email@acme.com',
    ]);
$builder->withCustomContextData([
        // ... more key-values
    ]);
$builder->withLabelData([
        // ... more key-values
    ]);
$agent = $builder->build();
```

All `with` methods of the support fluent chaining, so the previous example could be written as:

```php
$agent = (new \Nipwaayoni\AgentBuilder())
    ->withConfig(new Nipwaayoni\Config(['serviceName' => 'My Application']))
    ->withUserContextData([
        'id'    => 12345,
        'email' => 'email@acme.com',
    ])
    ->withCustomContextData([
        // ... more key-values
    ])
    ->withLabelData([
        // ... more key-values
    ])
    ->build();
```

This makes conditionally setting options easy:

```php
$builder = (new \Nipwaayoni\AgentBuilder())
    ->withConfig(new Nipwaayoni\Config(['serviceName' => 'My Application']));

if ($app->hasUser()) {
    $builder->withUserContextData([
        'id'    => $app->getUserId(),
        'email' => $app->getUserEmail(),
    ]);
}

$agent = $builder->build();
```

You can also provide a customized HTTP client, for example, to disable certificate validation:

```php
$agent = (new \Nipwaayoni\AgentBuilder())
    ->withConfig(new Nipwaayoni\Config(['serviceName' => 'My Application']))
    ->withHttpClient(new \Http\Adapter\Guzzle6\Client(new \GuzzleHttp\Client(['verify' => false])))
    ->build();
```
