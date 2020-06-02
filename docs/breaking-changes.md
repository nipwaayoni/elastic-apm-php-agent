
# Breaking Changes

## 6.x to 7.x
* The `EventFactoryInterface` has been changed, in case you are injecting your custom Event Factory, you will be affected.
* The methods `Transaction::setSpans`, `Transaction::getSpans`, `Transaction::getErrors` and `Transaction::setErrors` has been removed given the schema change rendered the these method unnecessary.
* The explicit use of the GuzzleHttp client has been replaced with the [php-http](http://docs.php-http.org/). This decreases the potential dependency conflicts but does place a burden on the consumer. See the [install document](install.md) for details.
* The first argument to the `Agent` constructor must now be a `Config` object rather than array of configuration values. 