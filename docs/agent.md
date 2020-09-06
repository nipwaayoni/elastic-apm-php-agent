# Creating an Agent

You will need to create an `Agent` object to manage events and send data to APM. A valid `Config` object must be provided during Agent creation.

## Creation with AgentBuilder::create()

The static `AgentBuilder::create()` method is the easiest approach if you only need to pass some configuration values to `Agent`.

```php
$agent = AgentBuilder::create(['appName' => 'My Application']);
```

The `create` method only accepts an array of valid configuration options. For more advanced `Agent` constructions, you must use the various `AgentBuilder` object methods.

## Creation with AgentBuilder Object Methods

The `AgentBuilder` class provides methods to create a configured `Agent`. The basic usage is:

```php
$builder = new \Nipwaayoni\AgentBuilder();
$builder->withConfig(new Nipwaayoni\Config([]));
$agent = $builder->build();
```

The following methods are available to influence the `Agent` creation:

```php
$builder->withAgentClass(string $className);
$builder->withConfigData(array $config);
$builder->withConfig(Config $config);
$builder->withUserContextData(array $context);
$builder->withCustomContextData(array $context);
$builder->withTagData(array $tags);
$builder->withEnvData(array $env);
$builder->withCookieData(array $cookies);
$builder->withEventFactory(EventFactoryInterface $eventFactory);
$builder->withTransactionStore(TransactionsStore $store);
$builder->withHttpClient(ClientInterface $httpClient);
$builder->withRequestFactory(RequestFactoryInterface $requestFactory);
$builder->withStreamFactory(StreamFactoryInterface $streamFactory);
```

All of the `with` methods support fluent chaining. See the [agent example](examples/agent-init.md) for more information.

Note 1: The methods ending with `Data` take an array and will eventually have companion methods that take an object. (See the `withConfigData()` and `withConfig()` methods for example.) The `Data` methods will be deprecated when objects are available.

Note 2: Previous versions of the `Agent` accepted an array of key/value pairs as the second argument to the constructor. These were used as the "shared context". Those contexts have been split into specific `with` methods for clarity. The values are unchanged, simply use the appropriate method corresponding to the array key used previously.

This approach to building the `Agent` allows developers to easily inject desired values/implementation without concern for the long list of constructor arguments. For maintainers, we will be able to change the Agent creation without causing major disruption to current consumers.

## Hooking the APM Request in the AgentBuilder

You can set pre and post hooks for the HTTP request used to send transaction event data to APM.

```php
$builder->withPreCommitCallback(callable $callback);
$builder->withPostCommitCallback(callable $callback);
```

Your callbacks should expect to be called as below and should not return a value. 

```php
$builder->withPreCommitCallback(function (RequestInterface $request) {
        // Request stuff
    });

$builder->withPostCommitCallback(function (ResponseInterface $response) {
        // Response stuff
    });
```

The primary purpose of these hooks is to enable logging or other diagnostics of the APM request/response.

## Creation Through Direct Instantiation (Deprecated)

An Agent object can created directly if necessary. Note that the constructor parameters have changed and are also now required. The `Agent` now relies on the caller to provide component implementations. We strongly recommend using the `AgentBuilder` for this purpose. The following example shows how to create an `Agent`:

```php
$agent = new \Nipwaayoni\Agent(
    new Nipwaayoni\Config(...), 
    new \Nipwaayoni\Contexts\ContextCollection(...),
    new \Nipwaayoni\Middleware\Connector(...),
    new \Nipwaayoni\Events\DefaultEventFactory(),
    new \Nipwaayoni\Stores\TransactionsStore()
);
```

## Extending the Agent Class

You can add your own agent functionality by extending the `Agent` class. Note that the `Agent::__construct()` method is declared final and you therefore cannot change the constructor signature when extending the class. The `AgentBuilder` must be able to create new agents with a fixed constructor signature.

When you extend the `Agent` class, you should override the `NAME` and `VERSION` class constants as appropriate for your class.

You can also override the `Agent::initialize()` method to execute any object setup. This method will be called by the `Agent::__construct()` method. It is not necessary to call `parent::initialize()`.

Use the `AgentBuilder::withAgentClass()` method to have the builder user your class. For example:

```php
$builder->withAgentClass(MyAgentClass::class);
```

If you need to provide other runtime data to your agent object, you will need to do so through objects methods called after construction.
