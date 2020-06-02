<?php


namespace Nipwaayoni;

use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\Psr17FactoryDiscovery;
use Nipwaayoni\Events\DefaultEventFactory;
use Nipwaayoni\Events\EventFactoryInterface;
use Nipwaayoni\Helper\Config;
use Nipwaayoni\Stores\TransactionsStore;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class AgentBuilder
{
    /** @var Config */
    private $config;

    /** @var array */
    private $sharedContexts;

    /** @var array */
    private $tags;

    /** @var array */
    private $env;

    /** @var array */
    private $cookies;

    /** @var EventFactoryInterface */
    private $eventFactory;

    /** @var TransactionsStore */
    private $transactionStore;

    /** @var ClientInterface */
    private $httpClient;

    /** @var RequestFactoryInterface */
    private $requestFactory;

    /** @var StreamFactoryInterface */
    private $streamFactory;

    public function __construct()
    {
        $this->init();
    }

    private function init(): void
    {
        $this->config = new Config(['appName' => 'APM Agent']);

        $this->sharedContexts = [
            'user' => [],
            'custom' => [],
        ];

        $this->tags = [];

        $this->env = [];

        $this->cookies = [];

        $this->eventFactory = new DefaultEventFactory();
        $this->transactionStore = new TransactionsStore();
        $this->httpClient = HttpClientDiscovery::find();
        $this->requestFactory = Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = Psr17FactoryDiscovery::findStreamFactory();
    }

    public function make(): Agent
    {
        return new Agent(
            $this->config,
            $this->makeSharedContext(),
            $this->eventFactory,
            $this->transactionStore,
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory
        );
    }

    private function makeSharedContext(): array
    {
        // Merge env and cookies into this to pass into EventBeans
        // That should be refactored later, but this class will hopefully
        // hid that change from consumers.
        return array_merge(
            $this->sharedContexts,
            [
                'tags' => $this->tags,
                'env' => $this->env,
                'cookies' => $this->cookies,
            ]
        );
    }

    public function withConfigData(array $config): self
    {
        $this->config = new Config($config);

        return $this;
    }

    public function withConfig(Config $config): self
    {
        $this->config = $config;

        return $this;
    }

    public function withUserContextData(array $context): self
    {
        $this->sharedContexts['user'] = $context;

        return $this;
    }

    public function withCustomContextData(array $context): self
    {
        $this->sharedContexts['custom'] = $context;

        return $this;
    }

    public function withTagData(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function withEnvData(array $env): self
    {
        $this->env = $env;

        return $this;
    }

    public function withCookieData(array $cookies): self
    {
        $this->config = $cookies;

        return $this;
    }

    public function withEventFactory(EventFactoryInterface $eventFactory): self
    {
        $this->eventFactory = $eventFactory;

        return $this;
    }

    public function withTransactionStore(TransactionsStore $store): self
    {
        $this->transactionStore = $store;

        return $this;
    }

    public function withHttpClient(ClientInterface $httpClient): self
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    public function withRequestFactory(RequestFactoryInterface $requestFactory): self
    {
        $this->requestFactory = $requestFactory;

        return $this;
    }

    public function withStreamFactory(StreamFactoryInterface $streamFactory): self
    {
        $this->streamFactory = $streamFactory;

        return $this;
    }
}
