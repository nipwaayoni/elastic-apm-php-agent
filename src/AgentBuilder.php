<?php


namespace Nipwaayoni;

use Nipwaayoni\Contexts\ContextCollection;
use Nipwaayoni\Events\DefaultEventFactory;
use Nipwaayoni\Events\EventFactoryInterface;
use Nipwaayoni\Exception\UnsupportedApmAgentImplementationException;
use Nipwaayoni\Middleware\Connector;
use Nipwaayoni\Stores\TransactionsStore;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

class AgentBuilder
{
    /** @var string  */
    private $agentClass = Agent::class;

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

    /** @var callable */
    private $preCommitCallback;

    /** @var callable */
    private $postCommitCallback;

    public static function create(array $config): Agent
    {
        return (new self())->withConfigData($config)->build();
    }

    public function __construct()
    {
        $this->init();
    }

    private function init(): void
    {
        $this->sharedContexts = [
            'user' => [],
            'custom' => [],
        ];

        $this->tags = [];

        $this->env = [];

        $this->cookies = [];
    }

    public function build(): ApmAgent
    {
        $config = $this->config ?? new Config(['appName' => 'APM Agent']);

        $connector = new Connector(
            $config->get('serverUrl'),
            $config->get('secretToken'),
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
            $this->preCommitCallback,
            $this->postCommitCallback
        );

        return new $this->agentClass(
            $config,
            $this->makeSharedContext(),
            $connector,
            $this->eventFactory ?? new DefaultEventFactory(),
            $this->transactionStore ?? new TransactionsStore()
        );
    }

    private function makeSharedContext(): ContextCollection
    {
        return new ContextCollection(array_merge(
            $this->sharedContexts,
            [
                'tags' => $this->tags,
                'env' => $this->env,
                'cookies' => $this->cookies,
            ]
        ));
    }

    public function withAgentClass(string $className): self
    {
        if (!in_array(ApmAgent::class, class_implements($className))) {
            throw new UnsupportedApmAgentImplementationException($className);
        }

        $this->agentClass = $className;

        return $this;
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
        $this->cookies = $cookies;

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

    public function withPreCommitCallback(callable $callback): self
    {
        $this->preCommitCallback = $callback;

        return $this;
    }

    public function withPostCommitCallback(callable $callback): self
    {
        $this->postCommitCallback = $callback;

        return $this;
    }
}
