# Converting debug_backtrace to a stack trace

There is a function on a span to set a stack trace but it uses a different format from PHP's `debug_backtrace()`.

In order to convert between the two you can use the setDebugBacktrace function.  It will convert details in the 
background and set the stack trace for you.

A simple example would be:

```php
$spanSt->setDebugBacktrace();
```

## Example Code
```php
use Nipwaayoni\Helper\StackTrace;

// create the agent
$agent = new \Nipwaayoni\Agent(['appName' => 'examples']);

$agent = new Agent($config);

// Span
// start a new transaction
$parent = $agent->startTransaction('POST /auth/examples/spans');

// burn some time
usleep(rand(10, 25));

// Create Span
$spanParent = $agent->factory()->newSpan('Authenication Workflow', $parent);
// $parent->incSpanCount();
$spanParent->start();

// Create another Span that is a parent span
$spanSt = $agent->factory()->newSpan('Span with stacktrace', $spanParent);
// $parent->incSpanCount();
$spanSt->start();

// burn some fictive time ..
usleep(rand(250, 350));
$spanSt->setDebugBacktrace();

$spanSt->stop();
$agent->putEvent($spanSt);

$spanParent->stop();

// Do some stuff you want to watch ...
usleep(rand(100, 250));

$agent->putEvent($spanParent);

$agent->stopTransaction($parent->getTransactionName());

// Force manual flush if needed
// $agent->send();
```

