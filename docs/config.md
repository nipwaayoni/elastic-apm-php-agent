# Configuration

The following parameters can be given as key/value pairs to the `Config` object used during `Agent` creation.

```
appName       : Name of this application, Required
appVersion    : Application version, Default: ''
serverUrl     : APM Server Endpoint, Default: 'http://127.0.0.1:8200'
secretToken   : Secret token for APM Server, Default: null
hostname      : Hostname to transmit to the APM Server, Default: gethostname()
active        : Activate the APM Agent, Default: true
backtraceLimit: Depth of a transaction backtrace, Default: unlimited
```

You may provide lists environment variables and HTTP cookies to include when sending events to APM. The `AgentBuilder` must be used to construct an `Agent` with these settings. 

```
withEnvData()    : $_SERVER vars to send to the APM Server, empty set sends all. Keys are case sensitive, Default: ['SERVER_SOFTWARE']
withCookiesData(): Cookies to send to the APM Server, empty set sends all. Keys are case sensitive, Default: []
```

## Example

```php
$config = new \Nipwaayoni\Helper\Config([
    'appName'     => 'My WebApp',
    'appVersion'  => '1.0.42',
    'serverUrl'   => 'http://apm-server.example.com',
    'secretToken' => 'DKKbdsupZWEEzYd4LX34TyHF36vDKRJP',
    'hostname'    => 'node-24.app.network.com',
]);

$agent = (new \Nipwaayoni\AgentBuilder())
    ->withConfig($config)
    ->withEnvData(['DOCUMENT_ROOT', 'REMOTE_ADDR', 'REMOTE_USER'])
    ->withCookieData(['my-cookie'])
    ->make();
```

**Note** The HTTP client can no longer be configured through the `Config` object. If you need to customize the HTTP client, you must construct and inject your own implementation. See the [Agent](agent.md) documentation for details.