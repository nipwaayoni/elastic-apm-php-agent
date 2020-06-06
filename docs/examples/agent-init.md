# Initialize the Agent

Note: While it is still possible to directly create an `Agent` object, this example uses the preferred `AgentBuilder` class.

The `AgentBuilder` is used to set all `Agent` options and make the final `Agent` object.

```php
// Minimal required usage
$agent = (new \Nipwaayoni\AgentBuilder())->withConfig(new Config(['appName' => 'My Application']))->make();

// Setting more options
$builder = new \Nipwaayoni\AgentBuilder();
$builder->withConfig(new Nipwaayoni\Config(['appName' => 'My Application']));
$builder->withUserContextData([
        'id'    => 12345,
        'email' => 'email@acme.com',
    ]);
$builder->withCustomContextData([
        // ... more key-values
    ]);
$builder->withTagData([
        // ... more key-values
    ]);
$agent = $builder->make();
```

All `with` methods of the support fluent chaining, so the previous example could be written as:

```php
$agent = (new \Nipwaayoni\AgentBuilder())
    ->withConfig(new Nipwaayoni\Config(['appName' => 'My Application']))
    ->withUserContextData([
        'id'    => 12345,
        'email' => 'email@acme.com',
    ])
    ->withCustomContextData([
        // ... more key-values
    ])
    ->withTagData([
        // ... more key-values
    ])
    ->make();
```

This makes conditionally setting options easy:

```php
$builder = (new \Nipwaayoni\AgentBuilder())
    ->withConfig(new Nipwaayoni\Config(['appName' => 'My Application']));

if ($app->hasUser()) {
    $builder->withUserContextData([
        'id'    => $app->getUserId(),
        'email' => $app->getUserEmail(),
    ]);
}

$agent = $builder->make();
```

You can also provide a customized HTTP client, for example, to disable certificate validation:

```php
$agent = (new \Nipwaayoni\AgentBuilder())
    ->withConfig(new Nipwaayoni\Config(['appName' => 'My Application']))
    ->withHttpClient(new \Http\Adapter\Guzzle6\Client(new \GuzzleHttp\Client(['verify' => false])))
    ->make();
```
