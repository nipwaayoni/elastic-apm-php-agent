<?php

namespace Nipwaayoni;

use Nipwaayoni\Exception\ConfigurationException;
use Nipwaayoni\Exception\Helper\UnsupportedConfigurationValueException;
use Nipwaayoni\Exception\MissingAppNameException;

/**
 *
 * Agent Config Store
 *
 */
class Config
{
    /**
     * Config Set
     *
     * @var array
     */
    private $config;

    /**
     * @param array $config
     * @throws ConfigurationException
     * @throws UnsupportedConfigurationValueException
     * @throws MissingAppNameException
     */
    public function __construct(array $config = [])
    {
        foreach (['httpClient', 'env', 'cookies'] as $removedKey) {
            if (array_key_exists($removedKey, $config)) {
                throw new UnsupportedConfigurationValueException($removedKey);
            }
        }

        if (isset($config['active']) && isset($config['enabled'])) {
            throw new ConfigurationException('Please provide only one of "active" or "enabled", preferring "enabled"');
        }

        // TODO prefer 'enabled' over 'active'

        // Register Merged Config
        $this->config = array_merge($this->getDefaultConfig(), $config);

        if (empty($this->config['appName'])) {
            throw new MissingAppNameException();
        }

        $this->config['serverUrl'] = rtrim($this->config['serverUrl'], '/');
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
        return ($this->config[$key]) ?? $default;
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
     * Get the Default Config of the Agent
     *
     * @return array
     */
    private function getDefaultConfig(): array
    {
        return [
            'serverUrl'             => $this->findServerUrl(),
            'secretToken'           => $this->findSecretToken(),
            'hostname'              => $this->findHostName(),
            'appName'               => $this->findAppName(),
            'appVersion'            => $this->findAppVersion(),
            'active'                => $this->findEnabled(),
            'enabled'               => $this->findEnabled(),
            'timeout'               => $this->findTimout(),
            'environment'           => $this->findEnvironment(),
            'backtraceLimit'        => $this->findBacktraceLimit(),
            'transactionSampleRate' => $this->findTransactionSampleRate(),
        ];
    }

    private function findServerUrl(): string
    {
        return $this->findConfigValue('server_url', 'http://127.0.0.1:8200');
    }

    private function findSecretToken(): ?string
    {
        return $this->findConfigValue('secret_token');
    }

    private function findHostName(): string
    {
        return $this->findConfigValue('hostname', gethostname());
    }

    private function findAppName(): ?string
    {
        return $this->findConfigValue('app_name');
    }

    private function findAppVersion(): string
    {
        return $this->findConfigValue('app_version', '');
    }

    private function findEnabled(): bool
    {
        $envValue = $this->findConfigValue('enabled');

        if (null === $envValue) {
            return true;
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
        return (int) $this->findConfigValue('backtrace_limit', 0);
    }

    private function findTransactionSampleRate(): float
    {
        return (float) $this->findConfigValue('transaction_sample_rate', 1.0);
    }

    private function findConfigValue(string $name, $default = null)
    {
        $envName = 'ELASTIC_APM_' . strtoupper($name);

        $envValue = getenv($envName, true) ?: getenv($envName);

        if ($envValue !== false) {
            return $envValue;
        }

        return $default;
    }
}
