# Elastic APM: PHP Agent

[![Build Status](https://travis-ci.com/nipwaayoni/elastic-apm-php-agent.svg?branch=master)](https://travis-ci.org/nipwaayoni/elastic-apm-php-agent)
[![Total Downloads](https://img.shields.io/packagist/dt/nipwaayoni/elastic-apm-php-agent.svg?style=flat)](https://packagist.org/packages/nipwaayoni/elastic-apm-php-agent)

This is a community PHP agent for Elastic.co's [APM](https://www.elastic.co/solutions/apm) solution, supporting the `v2` Intake API. Please note: This agent is not officially supported by [Elastic](https://www.elastic.co/).

## Documentation
* [Installation](https://github.com/nipwaayoni/elastic-apm-php-agent/blob/master/docs/install.md)
* [Breaking Changes](https://github.com/nipwaayoni/elastic-apm-php-agent/blob/master/docs/breaking-changes.md)
* [Configuration](https://github.com/nipwaayoni/elastic-apm-php-agent/blob/master/docs/config.md)
* [Knowledgebase](https://github.com/nipwaayoni/elastic-apm-php-agent/blob/master/docs/knowledgebase.md)

## Examples
* [Agent Initialization](https://github.com/nipwaayoni/elastic-apm-php-agent/blob/master/docs/examples/agent-init.md)
* [Basic Usage](https://github.com/nipwaayoni/elastic-apm-php-agent/blob/master/docs/examples/basic-usage.md)
* [Capture Throwable](https://github.com/nipwaayoni/elastic-apm-php-agent/blob/master/docs/examples/capture-throwable.md)
* [Spans](https://github.com/nipwaayoni/elastic-apm-php-agent/blob/master/docs/examples/spans.md)
* [Parent Transactions](https://github.com/nipwaayoni/elastic-apm-php-agent/blob/master/docs/examples/parent-transactions.php)
* [Metricset](https://github.com/nipwaayoni/elastic-apm-php-agent/blob/master/docs/examples/metricset.php)
* [Getting the Server Info](https://github.com/nipwaayoni/elastic-apm-php-agent/blob/master/docs/examples/server-info.php)
* [Distributed Tracing](https://github.com/nipwaayoni/elastic-apm-php-agent/blob/master/docs/examples/distributed-tracing.md)
* [Converting debug_backtrace to a stack trace](https://github.com/nipwaayoni/elastic-apm-php-agent/blob/master/docs/examples/convert-backtrace.md)

## Tests
```bash
vendor/bin/phpunit
```

## Contributors
A big thank you goes out to every contributor of this repo, special thanks goes out to:
* [philkra](https://github.com/philkra)
* [georgeboot](https://github.com/georgeboot)
* [alash3al](https://github.com/alash3al)
* [thinkspill](https://github.com/thinkspill)
* [YuZhenXie](https://github.com/YuZhenXie)
