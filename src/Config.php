<?php

namespace Nipwaayoni;

use Nipwaayoni\Exception\ConfigurationException;
use Nipwaayoni\Exception\Helper\UnsupportedConfigurationValueException;
use Nipwaayoni\Exception\MissingAppNameException;
use Psr\Log\LoggerInterface;
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
        'appName'               => 'app_name',
        'appVersion'            => 'app_version',
        'frameworkName'         => 'framework_name',
        'frameworkVersion'      => 'framework_version',
        'enabled'               => 'enabled',
        'timeout'               => 'timeout',
        'environment'           => 'environment',
        'backtraceLimit'        => 'backtrace_limit',
        'transactionSampleRate' => 'transaction_sample_rate',
    ];

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
     * @throws MissingAppNameException
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
        $this->logger = $values['logger'] ?? new NullLogger();

        foreach (['httpClient', 'env', 'cookies'] as $removedKey) {
            if (array_key_exists($removedKey, $values)) {
                throw new UnsupportedConfigurationValueException($removedKey);
            }
        }

        if (isset($values['active'])) {
            $this->logger->notice('The "active" configuration option is deprecated, please use "enabled" instead.');
        }

        $this->values = $values;
    }

    private function validateConfig(): void
    {
        if (empty($this->config['appName'])) {
            throw new MissingAppNameException();
        }

        $this->resolveActiveAndEnabled();

        $this->config['serverUrl'] = rtrim($this->config['serverUrl'], '/');
    }

    private function resolveActiveAndEnabled(): void
    {
        if (null !== $this->config['enabled'] && array_key_exists('active', $this->values)) {
            throw new ConfigurationException('Provide only one of "active" or "enabled", preferring "enabled"');
        }

        // Only enabled was specified
        if (null !== $this->config['enabled'] && !array_key_exists('active', $this->values)) {
            $this->values['active'] = $this->config['enabled'];
            return;
        }

        // Only active was specified
        if (null === $this->config['enabled'] && array_key_exists('active', $this->values)) {
            $this->config['enabled'] = $this->values['active'];
            return;
        }

        // Neither enabled or active were specified
        $this->config['enabled'] = true;
        $this->values['active'] = true;
    }

    private function logConfig(): void
    {
        $config = $this->asArray();

        if (!empty($config['secretToken'])) {
            $config['secretToken'] = preg_replace('/^(.).*(.)$/', '$1***$2', $config['secretToken']);
        }

        $message = implode(
            PHP_EOL,
            array_reduce(array_keys($config), function ($c, string $key) use ($config) {
                $c[] = sprintf('%s=%s', $key, $config[$key]);
                return $c;
            }, [])
        );

        $this->logger->debug('Runtime config: ' . PHP_EOL . $message);
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

    public function appName(): string
    {
        return $this->config['appName'];
    }

    public function appVersion(): ?string
    {
        return $this->config['appVersion'];
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

    public function backtraceLimit(): int
    {
        return $this->config['backtraceLimit'];
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
            'hostname'              => $this->findHostName(),
            'appName'               => $this->findAppName(),
            'appVersion'            => $this->findAppVersion(),
            'frameworkName'         => $this->findFrameworkName(),
            'frameworkVersion'      => $this->findFrameworkVersion(),
            'enabled'               => $this->findEnabled(),
            'timeout'               => $this->findTimout(),
            'environment'           => $this->findEnvironment(),
            'backtraceLimit'        => $this->findBacktraceLimit(),
            'transactionSampleRate' => $this->findTransactionSampleRate(),
        ];
    }

    private function findServerUrl(): string
    {
        return $this->findConfigValue('serverUrl', 'http://127.0.0.1:8200');
    }

    private function findSecretToken(): ?string
    {
        return $this->findConfigValue('secretToken');
    }

    private function findHostName(): string
    {
        return $this->findConfigValue('hostname', gethostname());
    }

    private function findAppName(): ?string
    {
        return $this->findConfigValue('appName');
    }

    private function findAppVersion(): ?string
    {
        return $this->findConfigValue('appVersion');
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

    private function findBacktraceLimit(): int
    {
        return (int) $this->findConfigValue('backtraceLimit', 0);
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
