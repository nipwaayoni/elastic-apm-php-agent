<?php

namespace Nipwaayoni\Middleware;

use Nipwaayoni\Agent;
use Nipwaayoni\Events\EventBean;
use Nipwaayoni\Stores\TransactionsStore;
use Nipwaayoni\Config;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 *
 * Connector which Transmits the Data to the Endpoints
 *
 */
class Connector
{
    public const APM_V2_ENDPOINT = 'intake/v2/events';
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var RequestFactoryInterface
     */
    private $requestFactory;

    /**
     * @var StreamFactoryInterface
     */
    private $streamFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    private $payload = [];

    /**
     * @param ClientInterface $client
     * @param RequestFactoryInterface $requestFactory
     * @param StreamFactoryInterface $streamFactory
     * @param Config $config
     */
    public function __construct(ClientInterface $client, RequestFactoryInterface $requestFactory, StreamFactoryInterface $streamFactory, Config $config)
    {
        $this->client = $client;
        $this->requestFactory = $requestFactory;
        $this->streamFactory = $streamFactory;
        $this->config = $config;
    }

    /**
     * Is the Payload Queue populated?
     *
     * @return bool
     */
    public function isPayloadSet(): bool
    {
        return (empty($this->payload) === false);
    }

    /**
     * Put Events to the Payload Queue
     *
     * @param EventBean $event
     */
    public function putEvent(EventBean $event)
    {
        $this->payload[] = json_encode($event);
    }

    /**
     * Commit the Events to the APM server
     *
     * @return bool
     * @throws ClientExceptionInterface
     */
    public function commit(): bool
    {
        $body = '';
        foreach ($this->payload as $line) {
            $body .= $line . "\n";
        }
        $this->payload = [];

        $request = $this->requestFactory
            ->createRequest('POST', $this->getEndpoint())
            ->withBody($this->streamFactory->createStream($body));

        $request = $this->populateRequestWithHeaders($request);

        $response = $this->client->sendRequest($request);

        return ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300);
    }

    /**
     * Get the Server Informations
     *
     * @link https://www.elastic.co/guide/en/apm/server/7.3/server-info.html
     *
     * @return Response
     */
    public function getInfo(): \GuzzleHttp\Psr7\Response
    {
        $request = $this->requestFactory
            ->createRequest('GET', $this->config->get('serverUrl'));

        $request = $this->populateRequestWithHeaders($request);

        return $this->client->sendRequest($request);
    }

    /**
     * Get the Endpoint URI of the APM Server
     *
     * @param string $endpoint
     *
     * @return string
     */
    private function getEndpoint(): string
    {
        return sprintf('%s/%s', $this->config->get('serverUrl'), self::APM_V2_ENDPOINT);
    }

    /**
     * @param RequestInterface $request
     *
     * @return RequestInterface
     */
    private function populateRequestWithHeaders(RequestInterface $request): RequestInterface
    {
        foreach ($this->getRequestHeaders() as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        return $request;
    }

    /**
     * Get the Headers for the POST Request
     *
     * @return array
     */
    private function getRequestHeaders(): array
    {
        // Default Headers Set
        $headers = [
            'Content-Type'     => 'application/x-ndjson',
            'User-Agent'       => sprintf('elasticapm-php/%s', Agent::VERSION),
            'Accept'           => 'application/json',
        ];

        // Add Secret Token to Header
        if ($this->config->get('secretToken') !== null) {
            $headers['Authorization'] = sprintf('Bearer %s', $this->config->get('secretToken'));
        }

        return $headers;
    }
}
