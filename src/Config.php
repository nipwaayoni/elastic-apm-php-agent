<?php

namespace Nipwaayoni;

use Nipwaayoni\Exception\ConfigurationException;
use Nipwaayoni\Exception\Helper\UnsupportedConfigurationValueException;
use Nipwaayoni\Exception\MissingServiceNameException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

/**
 *
 * Agent Config Store
 *
 */
class Config
{
    public const CONFIG_NAME_MAP = [
        'serverUrl'             => 'server_url',
        'secretToken'           => 'secret_token',
        'hostname'              => 'hostname',
        'serviceName'           => 'service_name',
        'serviceVersion'        => 'service_version',
        'frameworkName'         => 'framework_name',
        'frameworkVersion'      => 'framework_version',
        'enabled'               => 'enabled',
        'timeout'               => 'timeout',
        'environment'           => 'environment',
        'stackTraceLimit'       => 'stack_trace_limit',
        'transactionSampleRate' => 'transaction_sample_rate',
    ];

    private $legacyOptions = [
        'active' => ['name' => 'enabled', 'default' => true],
        'appName' => ['name' => 'serviceName', 'default' => null],
        'appVersion' => ['name' => 'serviceVersion', 'default' => null],
        'backtraceLimit' => ['name' => 'stackTraceLimit', 'default' => 0],
    ];

    /** @var string */
    private $defaultServiceName;
    /**
     * Config Set
     *
     * @var array
     */
    private $config;

    /** @var array */
    private $values;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param array $config
     * @throws ConfigurationException
     * @throws UnsupportedConfigurationValueException
     * @throws MissingServiceNameException
     */
    public function __construct(array $config = [])
    {
        $this->validateValues($config);

        $this->makeConfig();

        $this->validateConfig();

        $this->logConfig();
    }

    /**
     * @param array $values
     * @throws ConfigurationException
     * @throws UnsupportedConfigurationValueException
     */
    private function validateValues(array $values): void
    {
        $this->logger = new ApmLogger(
            $values['logger'] ?? new NullLogger(),
            $values['logLevel'] ?? LogLevel::INFO
        );

        foreach (['httpClient', 'env', 'cookies'] as $removedKey) {
            if (array_key_exists($removedKey, $values)) {
                throw new UnsupportedConfigurationValueException($removedKey);
            }
        }

        if (isset($values['defaultServiceName'])) {
            $this->defaultServiceName = $values['defaultServiceName'];
        }

        $this->values = $values;
    }

    private function validateConfig(): void
    {
        $this->resolveLegacyOptions();

        if (empty($this->config['serviceName'])) {
            throw new MissingServiceNameException();
        }

        // TODO validate serviceName matches ^[a-zA-Z0-9 _-]+$
        // TODO support list of server URLs

        $this->config['serverUrl'] = rtrim($this->config['serverUrl'], '/');
    }

    /**
     * This method should be completely removed any default values applied to the find* methods
     * when support for the legacy options is officially removed.
     *
     * @throws ConfigurationException
     */
    private function resolveLegacyOptions(): void
    {
        foreach ($this->legacyOptions as $legacyOption => $preferred) {
            $this->resolveLegacyOption($legacyOption, $preferred['name'], $preferred['default']);
        }
    }

    private function resolveLegacyOption(string $legacyOption, string $preferredOption, $preferredDefault): void
    {
        if (null !== $this->config[$preferredOption] && array_key_exists($legacyOption, $this->values)) {
            $this->logger->notice(
                sprintf('Both "%s" and "%s" were set, using the preferred option "%s".', $legacyOption, $preferredOption, $preferredOption)
            );
            $this->values[$legacyOption] = $this->config[$preferredOption];
            return;
        }

        // Only preferred was specified
        if (null !== $this->config[$preferredOption] && !array_key_exists($legacyOption, $this->values)) {
            $this->values[$legacyOption] = $this->config[$preferredOption];
            return;
        }

        // Only legacy was specified
        if (null === $this->config[$preferredOption] && array_key_exists($legacyOption, $this->values)) {
            $this->logger->notice(sprintf('The "%s" configuration option is deprecated, please use "%s" instead.', $legacyOption, $preferredOption));
            $this->config[$preferredOption] = $this->values[$legacyOption];
            return;
        }

        // Neither preferred or legacy were specified
        $this->config[$preferredOption] = $preferredDefault;
        $this->values[$legacyOption] = $preferredDefault;
    }

    private function logConfig(): void
    {
        $config = $this->asArray();

        if (!empty($config['secretToken'])) {
            $config['secretToken'] = preg_replace('/^(.).*(.)$/', '$1***$2', $config['secretToken']);
        }

        $message = json_encode($config);

        $this->logger->debug('Runtime config: ' . $message);
    }

    public function logger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Get Config Value
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed: value | null
     */
    public function get(string $key, $default = null)
    {
        $this->logger->notice(sprintf('Use of get("%s") is deprecated, please use the appropriate named accessor instead.', $key));

        // Try to return the config value first.
        if (array_key_exists($key, $this->config)) {
            return $this->config[$key];
        }

        // If there is no config value, try an input value.
        if (array_key_exists($key, $this->values)) {
            return $this->values[$key];
        }

        // Finally, return the default.
        return $default;
    }

    public function enabled(): bool
    {
        return $this->config['enabled'];
    }

    public function notEnabled(): bool
    {
        return !$this->config['enabled'];
    }

    public function serverUrl(): string
    {
        return $this->config['serverUrl'];
    }

    public function secretToken(): ?string
    {
        return $this->config['secretToken'];
    }

    public function transactionSampleRate(): float
    {
        return $this->config['transactionSampleRate'];
    }

    public function serviceName(): string
    {
        return $this->config['serviceName'];
    }

    public function serviceVersion(): ?string
    {
        return $this->config['serviceVersion'];
    }

    public function framework(): ?string
    {
        return $this->config['frameworkName'];
    }

    public function frameworkVersion(): ?string
    {
        return $this->config['frameworkVersion'];
    }

    public function timeout(): int
    {
        return $this->config['timeout'];
    }

    public function environment(): string
    {
        return $this->config['environment'];
    }

    public function stackTraceLimit(): int
    {
        return $this->config['stackTraceLimit'];
    }

    public function hostname(): string
    {
        return $this->config['hostname'];
    }

    /**
     * Get the all Config Set as array
     *
     * @return array
     */
    public function asArray(): array
    {
        return $this->config;
    }

    /**
     * Make the configuration of the Agent
     *
     * @return void
     */
    private function makeConfig(): void
    {
        $this->config = [
            'serverUrl'             => $this->findServerUrl(),
            'secretToken'           => $this->findSecretToken(),
            'hostname'              => $this->findHostname(),
            'serviceName'           => $this->findServiceName(),
            'serviceVersion'        => $this->findServiceVersion(),
            'frameworkName'         => $this->findFrameworkName(),
            'frameworkVersion'      => $this->findFrameworkVersion(),
            'enabled'               => $this->findEnabled(),
            'timeout'               => $this->findTimout(),
            'environment'           => $this->findEnvironment(),
            'stackTraceLimit'       => $this->findStackTraceLimit(),
            'transactionSampleRate' => $this->findTransactionSampleRate(),
        ];
    }

    private function findServerUrl(): string
    {
        return $this->findConfigValue('serverUrl', 'http://localhost:8200');
    }

    private function findSecretToken(): ?string
    {
        return $this->findConfigValue('secretToken');
    }

    private function findHostname(): ?string
    {
        return $this->findConfigValue('hostname', gethostname());
    }

    private function findServiceName(): ?string
    {
        return $this->findConfigValue('serviceName', $this->defaultServiceName);
    }

    private function findServiceVersion(): ?string
    {
        return $this->findConfigValue('serviceVersion');
    }

    private function findFrameworkName(): ?string
    {
        return $this->findConfigValue('frameworkName');
    }

    private function findFrameworkVersion(): ?string
    {
        return $this->findConfigValue('frameworkVersion');
    }

    private function findEnabled(): ?bool
    {
        $envValue = $this->findConfigValue('enabled');

        if (null === $envValue) {
            // This will change to 'return true' after removing the 'active' config option.
            return null;
        }

        if (is_bool($envValue)) {
            return $envValue;
        }

        return $envValue === 'true';
    }

    private function findTimout(): int
    {
        return (int) $this->findConfigValue('timeout', 10);
    }

    private function findEnvironment(): string
    {
        return $this->findConfigValue('environment', 'development');
    }

    private function findStackTraceLimit(): ?int
    {
        $limit = $this->findConfigValue('stackTraceLimit');

        if (null === $limit) {
            return null;
        }

        // Type casting makes null into an int and subsequent null checks fail.
        // This can be changed after the legacy backtraceLimit is removed and the
        // default value is set here. The return value will no longer be nullable
        // at that point.
        return (int) $limit;
    }

    private function findTransactionSampleRate(): float
    {
        return (float) $this->findConfigValue('transactionSampleRate', 1.0);
    }

    private function findConfigValue(string $name, $default = null)
    {
        if (isset($this->values[$name])) {
            return $this->values[$name];
        }

        $envName = 'ELASTIC_APM_' . strtoupper(self::CONFIG_NAME_MAP[$name]);

        $envValue = getenv($envName, true) ?: getenv($envName);

        if ($envValue !== false) {
            return $envValue;
        }

        return $default;
    }
}
