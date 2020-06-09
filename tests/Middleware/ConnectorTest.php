<?php

namespace Nipwaayoni\Tests\Middleware;

use GuzzleHttp\Psr7\Response;
use Http\Discovery\Psr17FactoryDiscovery;
use Nipwaayoni\Config;
use Nipwaayoni\Events\Transaction;
use Nipwaayoni\Middleware\Connector;
use Nipwaayoni\Tests\MakesHttpTransactions;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class ConnectorTest extends TestCase
{
    use MakesHttpTransactions;

    /** @var Connector  */
    private $connector;

    private $defaultConfigData = [
        'appName' => 'test',
        'serverUrl' => 'http://apm.example.com:8200',
        'secretToken' => 'abc123',
    ];

    /** @var Config  */
    private $config;

    /** @var RequestFactoryInterface  */
    private $requestFactory;
    /** @var StreamFactoryInterface  */
    private $streamFactory;

    public function setUp(): void
    {
        parent::setUp();

        $this->updateConfig();

        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
    }

    // TODO Requests to APM are the most important function of this package, we need more test coverage here

    public function testGetsInfoFromServerUrl(): void
    {
        $this->prepareClientWithResponses(new Response(200, ['Content-Type' => 'application/json'], '{
  "ok": {
    "build_date": "2018-07-27T18:49:58Z",
    "build_sha": "bc4d9a286a65b4283c2462404add86a26be61dca",
    "version": "7.0.0-alpha1"
  }
}'));

        $this->connector = new Connector($this->client, $this->requestFactory, $this->streamFactory, $this->config);

        $response = $this->connector->getInfo();

        // Response assertions
        $this->assertEquals(200, $response->getStatusCode());

        // Transaction Assertions
        $this->assertCount(1, $this->container);

        // Request Assertions
        $request = $this->container[0]->request();

        $this->assertEquals($this->defaultConfigData['serverUrl'], $request->getUri());
        $this->assertEquals('Bearer ' . $this->defaultConfigData['secretToken'], $request->getHeader('Authorization')[0]);
    }

    public function testSendsEventsToServerUrl(): void
    {
        $this->prepareClientWithResponses(new Response(202, []));

        $this->connector = new Connector($this->client, $this->requestFactory, $this->streamFactory, $this->config);

        $this->connector->putEvent(new Transaction('TestTransaction', []));
        $isSuccess = $this->connector->commit();

        // Response assertions
        $this->asserttrue($isSuccess);

        // Transaction Assertions
        $this->assertCount(1, $this->container);

        // Request Assertions
        $request = $this->container[0]->request();

        $this->assertStringContainsStringIgnoringCase(Connector::APM_V2_ENDPOINT, $request->getUri());
        $this->assertEquals('Bearer ' . $this->defaultConfigData['secretToken'], $request->getHeader('Authorization')[0]);

        $payload = json_decode($request->getBody()->getContents(), true);

        $this->assertEquals('TestTransaction', $payload['transaction']['name']);
    }

    private function updateConfig(array $configData = []): void
    {
        $this->config = new Config(array_merge($this->defaultConfigData, $configData));
    }
}
