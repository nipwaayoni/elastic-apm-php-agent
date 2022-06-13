<?php

namespace Nipwaayoni\Middleware;

use Http\Client\HttpAsyncClient;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Nipwaayoni\Agent;
use Nipwaayoni\Events\EventBean;
use Nipwaayoni\Stores\TransactionsStore;
use Nipwaayoni\Config;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 *
 * Connector which Transmits the Data to the Endpoints
 *
 */
class Connector implements LoggerAwareInterface
{
    public const APM_V2_ENDPOINT = 'intake/v2/events';

    private $userAgent = 'elasticapm-php/0.0';

    /**
     * @var string
     */
    private $serverUrl;

    /**
     * @var string|null
     */
    private $secretToken;

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
     * @var callable|null
     */
    private $preCommitCallback;

    /**
     * @var callable|null
     */
    private $postCommitCallback;

    /**
     * @var array
     */
    private $payload = [];

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param string $serverUrl
     * @param string $secretToken
     * @param ClientInterface $client
     * @param RequestFactoryInterface $requestFactory
     * @param StreamFactoryInterface $streamFactory
     * @param callable|null $preCommitCallback
     * @param callable|null $postCommitCallback
     */
    public function __construct(
        string $serverUrl,
        ?string $secretToken,
        ClientInterface $client = null,
        RequestFactoryInterface $requestFactory = null,
        StreamFactoryInterface $streamFactory = null,
        callable $preCommitCallback = null,
        callable $postCommitCallback = null
    ) {
        $this->serverUrl = $serverUrl;
        $this->secretToken = $secretToken;
        $this->client = $client ?? HttpClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
        $this->preCommitCallback = $preCommitCallback;
        $this->postCommitCallback = $postCommitCallback;

        $this->logger = new NullLogger();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function useHttpUserAgentString(string $userAgent): void
    {
        $this->userAgent = $userAgent;
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
     * @return void
     * @throws ClientExceptionInterface
     */
    public function commit(): void
    {
        $eventCount = count($this->payload);
        $body = '';
        foreach ($this->payload as $line) {
            $body .= $line . "\n";
        }
        $this->payload = [];

        $request = $this->requestFactory
            ->createRequest('POST', $this->getEndpoint())
            ->withBody($this->streamFactory->createStream($body));

        $request = $this->populateRequestWithHeaders($request);

        $this->logger->debug(sprintf('Prepared request with %s events.', $eventCount));

        $this->sendRequest($request);
    }

    private function sendRequest(RequestInterface $request): void
    {
        $this->preCommit($request);

        try {
            $response = $this->client->sendRequest($request);
            $this->logger->debug(sprintf('Sent request, response status: %s', $response->getStatusCode()));
            $this->postCommit($response);
        } catch (ClientExceptionInterface $e) {
            $this->logger->error(sprintf('Sending to APM failed, request error: %s', $e->getMessage()));
            $this->postCommit(null, $e);
        }
    }

    private function preCommit(RequestInterface $request): void
    {
        if (null === $this->preCommitCallback) {
            return;
        }

        $this->logger->debug('Calling pre-commit callback.');

        call_user_func($this->preCommitCallback, $request);
    }

    private function postCommit(?ResponseInterface $response = null, \Throwable $e = null): void
    {
        if (null === $this->postCommitCallback) {
            return;
        }

        $this->logger->debug('Calling post-commit callback.');

        call_user_func($this->postCommitCallback, $response, $e);
    }

    /**
     * Get the Server Informations
     *
     * @link https://www.elastic.co/guide/en/apm/server/7.3/server-info.html
     *
     * @return ResponseInterface
     */
    public function getInfo(): ResponseInterface
    {
        $request = $this->requestFactory
            ->createRequest('GET', $this->serverUrl);

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
        return sprintf('%s/%s', $this->serverUrl, self::APM_V2_ENDPOINT);
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
            'User-Agent'       => $this->userAgent,
            'Accept'           => 'application/json',
        ];

        // Add Secret Token to Header
        if ($this->secretToken !== null) {
            $headers['Authorization'] = sprintf('Bearer %s', $this->secretToken);
        }

        return $headers;
    }
}
