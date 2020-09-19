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

The Agent requires following options to function as expected.

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

### Legacy Options

The following options are deprecated in favor naming conventions adopted by other APM clients. While these still work at the moment, they are only supported as constructor arguments and are not available as environment variables.

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

withConfigData(array $config)
withConfig(Config $config)

withUserContextData(array $context)
withCustomContextData(array $context)

withTagData(array $tags)

withEnvData(array $env)

withCookieData(array $cookies)

withEventFactory(EventFactoryInterface $eventFactory)

withTransactionStore(TransactionsStore $store)

withHttpClient(ClientInterface $httpClient)

withRequestFactory(RequestFactoryInterface $requestFactory)

withStreamFactory(StreamFactoryInterface $streamFactory)

withPreCommitCallback(callable $callback)

withPostCommitCallback(callable $callback)

## Logging


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