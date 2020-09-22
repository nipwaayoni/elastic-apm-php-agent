# Configuration

There are two types of configuration you will use with the agent. Most operational options are given to the `Config` object when it is created, and that object then provides the configuration settings to other components. The following sections describe how to provide these settings.

The second type of configuration is setting options which influence what data is captured and sent to APM and accessing internal behaviors of the `Agent`. These options are provided to the `AgentBuilder` which then passes them to the `Agent` during construction.

## Config Object Options

The agent configuration can be provided through environment variables or as an associative array given to the `Config` class constructor. These methods may be intermixed.

The precedence is:

* Constructor arguments
* Environment variables
* Default values

### Required Options

The Agent requires the following options to function as expected.

#### Service Name

| Environment | Config Key | Default |
|-------------|------------|---------|
| ELASTIC_APM_SERVICE_NAME | serviceName | none |

The name of your service. This is the primary point of aggregation when viewing data in the APM UI.

This is the only configuration option you are required to provide when creating an Agent.

**Note:** The service name must confirm to the regular expression `^[a-zA-Z0-9 _-]+$` (ASCII alphabet, numbers, dashes, underscores and spaces).

#### Server URL

| Environment | Config Key | Default |
|-------------|------------|---------|
| ELASTIC_APM_SERVER_URL | serverUrl | http://localhost:8200 |

The URL for your APM service. The URL must be fully qualified, including the protocol and port.

### Other Options

#### Enabled

| Environment | Config Key | Default |
|-------------|------------|---------|
| ELASTIC_APM_ENABLED | enabled | true |

Enable or disable the sending of data to APM. When not enabled, the Agent may still collect event data, but will not attempt to send data to the APM service.

#### Default Service Name

| Environment | Config Key | Default |
|-------------|------------|---------|
| *N/A* | defaultServiceName | none |

The default value to use as the service name if none other is given. This is intended to support frameworks wishing to make the `Agent` available to users with minimal configuration. This option should be used rather than setting `serviceName` so that the user is free to use an environment variable or `Config` option as they choose.

#### Service Version

| Environment | Config Key | Default |
|-------------|------------|---------|
| ELASTIC_APM_SERVICE_VERSION | serviceVersion | none |

The version of your deployed service.

#### Secret Token

| Environment | Config Key | Default |
|-------------|------------|---------|
| ELASTIC_APM_SECRET_TOKEN | secretToken | none |

The secret token required to send data to your APM service.

#### Hostname

| Environment | Config Key | Default |
|-------------|------------|---------|
| ELASTIC_APM_HOSTNAME | hostname | `gethostname()` |

The OS hostname on which the agent is running.

#### Framework Name

| Environment | Config Key | Default |
|-------------|------------|---------|
| ELASTIC_APM_FRAMEWORK_NAME | frameworkName | none |

The name of the application framework, if any.

#### Framework Version

| Environment | Config Key | Default |
|-------------|------------|---------|
| ELASTIC_APM_FRAMEWORK_VERSION | frameworkVersion | none |

The version of the application framework, if any.

#### Stack Trace Limit

| Environment | Config Key | Default |
|-------------|------------|---------|
| ELASTIC_APM_STACK_TRACE_LIMIT | stackTraceLimit | 0 |

Depth of a transaction stack trace. The default (0) is unlimited depth.

#### Transaction Sample Rate

| Environment | Config Key | Default |
|-------------|------------|---------|
| ELASTIC_APM_TRANSACTION_SAMPLE_RATE | transactionSampleRate | 1.0 |

Transactions will be sampled at the given rate (1.0 being 100%). Sampling a transaction means that the context and child events will be included in the data sent to APM. Unsampled transactions are still reported to APM, including the overall transaction time, but will have no details. The default is to sample all (1.0) transactions.

### Legacy Options

The following options are deprecated in favor of naming conventions adopted by other APM clients. While these still work at the moment, they are only supported as constructor arguments and are not available as environment variables.

#### App Name

| Environment | Config Key | Default |
|-------------|------------|---------|
| *N/A* | appName | none |

Use the `service name` configuration option instead.

#### App Version

| Environment | Config Key | Default |
|-------------|------------|---------|
| *N/A* | appVersion | none |

Use the `service version` configuration option instead.

#### Active

| Environment | Config Key | Default |
|-------------|------------|---------|
| *N/A* | active | true |

Use the `enabled` configuration option instead.

#### Backtrace Limit

| Environment | Config Key | Default |
|-------------|------------|---------|
| *N/A* | backtraceLimit | 0 |

Use the `stack trace limit` configuration option instead.

## AgentBuilder Options

You can use the following `AgentBuilder` methods to specify data for the `Agent` to collect and send to APM. Please see the [agent documentation](agent.md) for more information.

### Configuration

Configuration can be supplied either as an array of key/value pairs (legacy) or as a Config object (preferred). See the preceding section regarding configuration options for details.

Note that the context data set here is shared by all transactions and other events created by the resulting `Agent` and will be merged with locally provided context data. Therefore, only system level, common context data should be provided. In a typical request/response PHP application, a new `Agent`, and therefore new context data, is likely created for each request. However, long running worker queues may need to consider what "context" means.

```php
$builder->withConfigData(array $config);
$builder->withConfig(Config $config);
```

Note that you should use only one of the two methods as any previous configuration will be replaced.

### User Context

User context must be an array of key/value pairs as expected by APM. See the [user context docs](https://www.elastic.co/guide/en/apm/get-started/current/metadata.html#user-fields) for more.

```php
$context = [
    'email' => 'bob@example.com',
    'name' => 'Bob Smith',
    'id' => '123',
];

$builder->withUserContextData(array $context);
```

### Custom Context

Custom context must be an array of key/value pairs. See the [custom context docs](https://www.elastic.co/guide/en/apm/get-started/current/metadata.html#custom-fields) for more.

```php
$context = [
    'my-fact' => 'something interesting',
];

$builder->withCustomContextData(array $context);
```

### Tags

Tags are indexed data added to APM events. (This was changed to `labels` in the APM 7.0 release and this module will be updated to reflect that. See the [APM docs](https://www.elastic.co/guide/en/apm/get-started/current/metadata.html#labels-fields) for more information.)

```php
$builder->withTagData(array $tags);
```

### Environment Variables

If used, only the provided environment variables (from PHP `$_SERVER`) will be sent to APM. 

**WARNING!** If this list is not provided or is empty, all values from `$_SERVER` will be sent, which may expose sensitive information. 

```php
$builder->withEnvData(array $env);
```

Note that the `Agent` will always remove `ELASTIC_APM_*` variables before sending, regardless of the provided list.

### Cookies

If used, only the provided cookie values (from PHP `$_COOKIE`) will be sent to APM.

```php
$builder->withCookieData(array $cookies);
```

## Logging

The `Agent` can use a [PSR-3](https://www.php-fig.org/psr/psr-3/) compatible logger object. You must supply a valid object using the `logger` key in the `Config` constructor arguments:

```php
$logger = new Logger('name');

$agent = AgentBuilder::create(['logger' => $logger]);
```

## Example

```php
putenv('ELASTIC_APM_SECRET_TOKEN=DKKbdsupZWEEzYd4LX34TyHF36vDKRJP');

$config = new Nipwaayoni\Config([
    'serviceName'     => 'My WebApp',
    'serviceVersion'  => '1.0.42',
    'serverUrl'       => 'http://apm-server.example.com',
    'hostname'        => 'node-24.app.network.com',
]);

$agent = (new \Nipwaayoni\AgentBuilder())
    ->withConfig($config)
    ->withEnvData(['DOCUMENT_ROOT', 'REMOTE_ADDR', 'REMOTE_USER'])
    ->withCookieData(['my-cookie'])
    ->build();
```

**Note** The HTTP client can no longer be configured through the `Config` object. If you need to customize the HTTP client, you must construct and inject your own implementation. See the [Agent](agent.md) documentation for details.