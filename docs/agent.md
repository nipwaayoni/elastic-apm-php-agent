# Creating an Agent

You will need to create an `Agent` object to manage events and send data to APM. A valid `Config` object must be provided during Agent creation.

## Creation with AgentBuilder

The `AgentBuilder` class provides methods to create a configured `Agent`. The basic usage is:

```php
$builder = new \Nipwaayoni\AgentBuilder();
$builder->withConfig(new Nipwaayoni\Config([]));
$agent = $builder->make();
```

The following methods are available to influence the `Agent` creation:

```php
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

## Direct Creation (Deprecated)

An Agent object can created directly:

```php
$agent = new \Nipwaayoni\Agent(new Nipwaayoni\Config([]));
```

Note 1: Previous versions of the Agent accepted an array of configuration values and created the `Config` object internally. That is no longer supported and you must now create the `Config` object and pass it into the constructor.

Note 2: The `Agent` constructor accepts a number of optional parameters. Those parameters will be made required in a future release and the `AgentBuilder` class will assume responsibility for initializing the `Agent` with defaults. We strongly encourage moving to the `AgentBuilder` approach now.

