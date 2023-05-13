<?php

namespace Nipwaayoni\Tests\Middleware;

use GuzzleHttp\Psr7\Response;
use Nipwaayoni\Events\Transaction;
use Nipwaayoni\Middleware\Connector;
use Nipwaayoni\Middleware\Credential;
use Nipwaayoni\Middleware\CredentialSecretToken;
use Nipwaayoni\Tests\MakesHttpTransactions;
use PHPUnit\Framework\TestCase;

class ConnectorTest extends TestCase
{
    use MakesHttpTransactions;

    private $serverUrl = 'http://apm.example.com:8200';
    private $secretToken = 'abc123';

    /** @var Credential */
    private $credential;

    // TODO Requests to APM are the most important function of this package, we need more test coverage here

    public function __construct()
    {
        parent::__construct();

        $this->credential = new CredentialSecretToken($this->secretToken);
    }

    public function testGetsInfoFromServerUrl(): void
    {
        $this->prepareClientWithResponses(new Response(200, ['Content-Type' => 'application/json'], '{
  "ok": {
    "build_date": "2018-07-27T18:49:58Z",
    "build_sha": "bc4d9a286a65b4283c2462404add86a26be61dca",
    "version": "7.0.0-alpha1"
  }
}'));

        $connector = new Connector($this->serverUrl, $this->credential, $this->client);

        $response = $connector->getInfo();

        // Response assertions
        $this->assertEquals(200, $response->getStatusCode());

        // Transaction Assertions
        $this->assertCount(1, $this->container);

        // Request Assertions
        $request = $this->container[0]->request();

        $this->assertEquals($this->serverUrl, $request->getUri());
        $this->assertEquals('Bearer ' . $this->secretToken, $request->getHeader('Authorization')[0]);
    }

    public function testSendsEventsToServerUrl(): void
    {
        $this->prepareClientWithResponses(new Response(202, []));

        $connector = new Connector($this->serverUrl, $this->credential, $this->client);

        $connector->putEvent(new Transaction('TestTransaction', []));
        $connector->commit();

        // Transaction Assertions
        $this->assertCount(1, $this->container);

        // Request Assertions
        $request = $this->container[0]->request();

        $this->assertStringContainsStringIgnoringCase(Connector::APM_V2_ENDPOINT, $request->getUri());
        $this->assertEquals('Bearer ' . $this->secretToken, $request->getHeader('Authorization')[0]);

        $payload = json_decode($request->getBody()->getContents(), true);

        $this->assertEquals('TestTransaction', $payload['transaction']['name']);
    }

    public function testSendsEventsWithGivenUserAgent(): void
    {
        $this->prepareClientWithResponses(new Response(202, []));

        $connector = new Connector($this->serverUrl, $this->credential, $this->client);
        $connector->useHttpUserAgentString('my-agent/1.0');

        $connector->putEvent(new Transaction('TestTransaction', []));
        $connector->commit();

        // Request Assertions
        $request = $this->container[0]->request();

        $this->assertEquals('my-agent/1.0', $request->getHeader('User-Agent')[0]);
    }
}
