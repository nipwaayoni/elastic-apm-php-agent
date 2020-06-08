<?php

namespace Nipwaayoni\Tests\Middleware;

use GuzzleHttp\Psr7\Response;
use Http\Discovery\Psr17FactoryDiscovery;
use Nipwaayoni\Config;
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

    private $configData = [
        'appName' => 'test',
        'serverUrl' => 'http://apm.example.com:8200',
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

        $this->config = new Config($this->configData);

        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
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

        $this->connector = new Connector($this->client, $this->requestFactory, $this->streamFactory, $this->config);

        $response = $this->connector->getInfo();

        // Response assertions

        // Transaction Assertions
        $this->assertCount(1, $this->container);

        // Request Assertions
        $request = $this->container[0]->request();

        $this->assertEquals($this->configData['serverUrl'], $request->getUri());
    }
}
