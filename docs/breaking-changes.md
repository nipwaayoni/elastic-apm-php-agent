
# Breaking Changes

## 7.5

* The `Agent::send()` method no longer returns a `bool`. The method now has a `void` return type and will rely on exceptions to communicate failure.
* The `Connector::commit()` method no longer returns a `bool`. The method now has a `void` return type and will rely on exceptions to communicate failure.
* `Span` objects now default to "sync: true" indicating they are blocking. The APM schema defines `sync` as "Indicates whether the span was executed synchronously or asynchronously." Since most PHP execution is synchronous, this default makes sense. A new `AsyncSpan` class has been added to represent asynchronously executed spans.
* The `EventFactoryInterface` now includes a `newAsyncSpan` method which must be implemented.

### Deprecated

* The `AgentBuilder::withTagData()` method is deprecated in favor of `AgentBuilder::withLabelData()`, in keeping with Elastic and other agents.
* The configuration options `active`, `appName`, `appVersion` and `backtraceLimit` are all deprecated in favor of more commonly used names. See the [configuration legacy options](config.md) for details and alternatives.
* The `Config::get()` method is deprecated in favor of named accessors. Furthermore, the behavior of the `Config` class to carry arbitrary key/value pairs is also deprecated. A future release will only allow known configuration keys.

## 6.x to 7.x
* The `EventFactoryInterface` has been changed, in case you are injecting your custom Event Factory, you will be affected.
* The methods `Transaction::setSpans`, `Transaction::getSpans`, `Transaction::getErrors` and `Transaction::setErrors` has been removed given the schema change rendered the these method unnecessary.
* The explicit use of the GuzzleHttp client has been replaced with the [php-http](http://docs.php-http.org/). This decreases the potential dependency conflicts but does place a burden on the consumer. See the [install document](install.md) for details.
* The first argument to the `Agent` constructor must now be a `Config` object rather than an array of configuration values.
* The second argument to the `Agent` constructor must now be a `ContextCollection` object rather than an array of context values.
* The `Agent` constructor parameters have changed and are now required. Direct creation of an `Agent` is discouraged in favor of using the `AgentBuilder` class.
* The `Config` class no longer accepts `httpClient`, `env` or `cookies` keys and will cause an `UnsupportedConfigurationValueException` if given.
* Specifying environment variable and HTTP cookie names to include in APM events must now be done through a `ContextCollection` object or through the `AgentBuilder` class.
* `Transaction` class constructors no longer accept a start time. You must now pass the start time to the `Transaction::start()` method consistent with `Span` objects.
* The `EventFactoryInterface::newTransaction` method signature has changed to remove the `$start` argument.
