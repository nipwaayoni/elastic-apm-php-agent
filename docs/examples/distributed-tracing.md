# Distributed Tracing

Distributed tracing allows Elastic APM to associate sub-transactions from other systems which are involved in fullfilling a transaction with a primary system. For example, your web application may call multiple other systems via REST when responding to a request for a web page. Your web application transaction can record a span representing the HTTP request to a REST resource, but cannot directly know how that request is fulfilled. If the remove REST service also records application data to APM, distributed tracing allows APM to associate the two requests when you view the parent.

## Screenshots
![Dashboard](https://github.com/nipwaayoni/elastic-apm-php-agent/blob/master/docs/examples/blob/dt_dashboard.png "Distributed Tracing Dashboard")

You enable distributed tracing by including a `traceparent` header containing an appropriate ID when you request another resource. Elastic APM has [adopted the W3C TraceContext](https://www.elastic.co/blog/elastic-apm-adopts-w3c-tracecontext) for this purpose.

`TraceableEvent` objects (`Transaction` and `Span` objects) provide helper methods to assist in constructing the required `traceparent` header. 

## Example Code
```php
// Assume we have an existing transaction, and let it help us

// Get a string if using something like curl
$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL            => 'http://127.0.0.1:5001',
    CURLOPT_HTTPHEADER     => [$transaction->traceHeaderAsString()],
]);

// Or use a \Psr\Http\Message\RequestInterface compatible object
$request = new \GuzzleHttp\Psr7\Request('GET', 'https://example.com');

// Get an array representation
$headerParts = $transaction->traceHeaderAsArray();
$request = $request->withHeader($headerParts['name'], $headerParts['value']);

// Or let the event do the work on a RequestInterface
$request = $transaction->addTraceHeaderToRequest($request);
```
