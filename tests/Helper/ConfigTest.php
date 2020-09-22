<?php

namespace Nipwaayoni\Tests\Helper;

use Nipwaayoni\Exception\ConfigurationException;
use Nipwaayoni\Exception\Helper\UnsupportedConfigurationValueException;
use Nipwaayoni\Config;
use Nipwaayoni\Tests\TestCase;
use Psr\Log\Test\TestLogger;

/**
 * Test Case for @see \Nipwaayoni\Config
 */
final class ConfigTest extends TestCase
{
    public function tearDown(): void
    {
        parent::tearDown();

        foreach (array_keys(getenv()) as $envName) {
            if (strpos($envName, 'ELASTIC_APM') !== 0) {
                continue;
            }

            putenv($envName);
        }
    }

    /**
     * @covers \Nipwaayoni\Config::__construct
     * @covers \Nipwaayoni\Agent::getConfig
     * @covers \Nipwaayoni\Config::asArray
     */
    public function testControlDefaultConfig()
    {
        $appName = sprintf('app_name_%d', rand(10, 99));
        $config = (new Config([ 'serviceName' => $appName, 'active' => false]))->asArray();

        $this->assertArrayHasKey('serviceName', $config);
        $this->assertArrayHasKey('secretToken', $config);
        $this->assertArrayHasKey('serverUrl', $config);
        $this->assertArrayHasKey('hostname', $config);
        $this->assertArrayHasKey('enabled', $config);
        $this->assertArrayHasKey('timeout', $config);
        $this->assertArrayHasKey('serviceVersion', $config);
        $this->assertArrayHasKey('environment', $config);
        $this->assertArrayHasKey('stackTraceLimit', $config);
        $this->assertArrayHasKey('transactionSampleRate', $config);

        $this->assertEquals($appName, $config['serviceName']);
        $this->assertNull($config['secretToken']);
        $this->assertEquals('http://localhost:8200', $config['serverUrl']);
        $this->assertEquals(gethostname(), $config['hostname']);
        $this->assertFalse($config['enabled']);
        $this->assertEquals(10, $config['timeout']);
        $this->assertEquals('development', $config['environment']);
        $this->assertEquals(0, $config['stackTraceLimit']);
        $this->assertEquals(1, $config['transactionSampleRate']);
    }

    /**
     * @depends testControlDefaultConfig
     *
     * @covers \Nipwaayoni\Config::__construct
     * @covers \Nipwaayoni\Agent::getConfig
     * @covers \Nipwaayoni\Config::asArray
     */
    public function testControlInjectedConfig()
    {
        $init = [
            'serviceName'     => sprintf('app_name_%d', rand(10, 99)),
            'secretToken'     => hash('tiger128,3', time()),
            'serverUrl'       => sprintf('https://node%d.domain.tld:%d', rand(10, 99), rand(1000, 9999)),
            'serviceVersion'  => sprintf('%d.%d.42', rand(0, 3), rand(0, 10)),
            'frameworkName'   => uniqid(),
            'timeout'         => rand(10, 20),
            'hostname' => sprintf('host_%d', rand(0, 9)),
            'enabled'         => false,
        ];

        $config = (new Config($init))->asArray();

        foreach ($init as $key => $value) {
            $this->assertEquals($config[$key], $init[$key], 'key: ' . $key);
        }
    }

    /**
     * @depends testControlInjectedConfig
     *
     * @covers \Nipwaayoni\Config::__construct
     * @covers \Nipwaayoni\Agent::getConfig
     * @covers \Nipwaayoni\Config::get
     */
    public function testGetConfig()
    {
        $init = [
            'serviceName' => sprintf('app_name_%d', rand(10, 99)),
            'active'  => false,
        ];

        $config = new Config($init);

        $this->assertEquals($config->serviceName(), $init['serviceName']);
    }

    /**
     * @depends testControlDefaultConfig
     *
     * @covers \Nipwaayoni\Config::__construct
     * @covers \Nipwaayoni\Agent::getConfig
     * @covers \Nipwaayoni\Config::asArray
     */
    public function testTrimElasticServerUrl()
    {
        $init = [
            'serverUrl' => 'http://foo.bar/',
            'serviceName'   => sprintf('app_name_%d', rand(10, 99)),
            'enabled'   => false,
        ];

        $config = (new Config($init))->asArray();

        foreach ($init as $key => $value) {
            if ('serverUrl' === $key) {
                $this->assertEquals('http://foo.bar', $config[$key]);
            } else {
                $this->assertEquals($config[$key], $init[$key], 'key: ' . $key);
            }
        }
    }

    /**
     * @throws UnsupportedConfigurationValueException
     * @throws \Nipwaayoni\Exception\MissingServiceNameException
     *
     * @dataProvider unsupportedConfigOptions
     */
    public function testThrowsExceptionIfUnsupportedOptionIsIncluded(string $option): void
    {
        $this->expectException(UnsupportedConfigurationValueException::class);

        new Config([
            'serviceName' => 'Test',
            $option => ['name' => 'test'],
        ]);
    }

    public function unsupportedConfigOptions(): array
    {
        return [
            'environment' => ['env'],
            'cookies' => ['cookies'],
            'http client' => ['httpClient'],
        ];
    }

    public function testMakesArbitraryConfigValuesAvailable(): void
    {
        $config = new Config(['serviceName' => 'Test', 'my-item' => 'some value']);

        $this->assertEquals('some value', $config->get('my-item'));
    }

    public function testSetsServiceNameFromEnvironmentVariable(): void
    {
        putenv(sprintf('ELASTIC_APM_SERVICE_NAME=%s', 'My Test App'));

        $config = new Config();

        $this->assertEquals('My Test App', $config->serviceName());
    }

    public function testSetsServiceNameFromConstructorArgument(): void
    {
        $config = new Config(['serviceName' => 'My Test App']);

        $this->assertEquals('My Test App', $config->serviceName());
    }

    public function testUsesDefaultServiceNameWhenGiven(): void
    {
        $config = new Config(['defaultServiceName' => 'My Default Test App']);

        $this->assertEquals('My Default Test App', $config->serviceName());
    }

    public function testIgnoresDefaultServiceNameWhenEnvironmentVariableIsSet(): void
    {
        putenv(sprintf('ELASTIC_APM_SERVICE_NAME=%s', 'My Test App'));

        $config = new Config(['defaultServiceName' => 'My Default Test App']);

        $this->assertEquals('My Test App', $config->serviceName());
    }

    public function testIgnoresDefaultServiceNameWhenConstructorArgumentIsSet(): void
    {
        $config = new Config(['serviceName' => 'My Test App', 'defaultServiceName' => 'My Default Test App']);

        $this->assertEquals('My Test App', $config->serviceName());
    }

    public function testConstructorArgumentTakesPrecedenceOverEnvironmentVariable(): void
    {
        putenv(sprintf('ELASTIC_APM_APP_NAME=%s', 'My Test App'));

        $config = new Config(['serviceName' => 'Test']);

        $this->assertEquals('Test', $config->serviceName());
    }

    /**
     * @throws UnsupportedConfigurationValueException
     * @throws \Nipwaayoni\Exception\MissingServiceNameException
     *
     * @dataProvider environmentVariableChecks
     */
    public function testAllowsSettingOptionsWithEnvironmentVariables(string $envName, string $envValue, string $configName, $configValue): void
    {
        $envFullName = 'ELASTIC_APM_' . strtoupper($envName);

        putenv(sprintf('%s=%s', $envFullName, $envValue));

        $config = new Config(['serviceName' => 'Test']);

        $this->assertEquals($configValue, $config->$configName());
    }

    public function environmentVariableChecks(): array
    {
        // Service Name is tested separately
        return [
            'server url' => [
                'server_url',
                'https://example.com:8200',
                'serverUrl',
                'https://example.com:8200',
            ],
            'secret token' => [
                'secret_token',
                'abc123',
                'secretToken',
                'abc123',
            ],
            'hostname' => [
                'hostname',
                'node1.example.com',
                'hostname',
                'node1.example.com',
            ],
            'service version' => [
                'service_version',
                '1.2',
                'serviceVersion',
                '1.2',
            ],
            'not enabled (check enabled)' => [
                'enabled',
                'false',
                'enabled',
                false,
            ],
            'not enabled (check notEnabled)' => [
                'enabled',
                'false',
                'notEnabled',
                true,
            ],
            'enabled (check enabled)' => [
                'enabled',
                'true',
                'enabled',
                true,
            ],
            'enabled (check notEnabled)' => [
                'enabled',
                'true',
                'notEnabled',
                false,
            ],
            'timeout' => [
                'timeout',
                '15',
                'timeout',
                15,
            ],
            'environment' => [
                'environment',
                'production',
                'environment',
                'production',
            ],
            'stack trace limit' => [
                'stack_trace_limit',
                '10',
                'stackTraceLimit',
                10,
            ],
            'transaction sample rate' => [
                'transaction_sample_rate',
                '.25',
                'transactionSampleRate',
                .25,
            ],
        ];
    }

    /**
     * @throws UnsupportedConfigurationValueException
     * @throws \Nipwaayoni\Exception\MissingServiceNameException
     *
     * @dataProvider constructorArgumentsChecks
     */
    public function testAllowsSettingOptionsWithConstructorArguments(string $optionName, string $optionValue, string $configName, $configValue): void
    {
        $config = new Config(['serviceName' => 'Test', $optionName => $optionValue]);

        $this->assertEquals($configValue, $config->$configName());
    }

    public function constructorArgumentsChecks(): array
    {
        // Service Name is tested separately
        return [
            'server url' => [
                'serverUrl',
                'https://example.com:8200',
                'serverUrl',
                'https://example.com:8200',
            ],
            'secret token' => [
                'secretToken',
                'abc123',
                'secretToken',
                'abc123',
            ],
            'hostname' => [
                'hostname',
                'node1.example.com',
                'hostname',
                'node1.example.com',
            ],
            'service version' => [
                'serviceVersion',
                '1.2',
                'serviceVersion',
                '1.2',
            ],
            'not enabled (check enabled)' => [
                'enabled',
                'false',
                'enabled',
                false,
            ],
            'not enabled (check notEnabled)' => [
                'enabled',
                'false',
                'notEnabled',
                true,
            ],
            'enabled (check enabled)' => [
                'enabled',
                'true',
                'enabled',
                true,
            ],
            'enabled (check notEnabled)' => [
                'enabled',
                'true',
                'notEnabled',
                false,
            ],
            'timeout' => [
                'timeout',
                '15',
                'timeout',
                15,
            ],
            'environment' => [
                'environment',
                'production',
                'environment',
                'production',
            ],
            'backtrace limit' => [
                'stackTraceLimit',
                '10',
                'stackTraceLimit',
                10,
            ],
            'transaction sample rate' => [
                'transactionSampleRate',
                '.25',
                'transactionSampleRate',
                .25,
            ],
        ];
    }

    /**
     * @dataProvider deprecatedOptionsChecks
     */
    public function testLogsNoticeWhenUsingDeprecatedOptions(string $legacyOption, $optionValue, string $preferredOption): void
    {
        $logger = new TestLogger();

        $options = [$legacyOption => $optionValue, 'logger' => $logger];
        if ($preferredOption !== 'serviceName') {
            $options['serviceName'] = 'Test';
        }

        new Config($options);

        $this->assertTrue($logger->hasNoticeThatContains(
            sprintf('The "%s" configuration option is deprecated, please use "%s" instead.', $legacyOption, $preferredOption)
        ));
    }

    /**
     * @dataProvider deprecatedOptionsChecks
     */
    public function testUsesPreferredOptionOverLegacyWhenBothArePresent(string $legacyOption, $optionValue, string $preferredOption): void
    {
        $options = [$legacyOption => $optionValue];
        if ($preferredOption !== 'serviceName') {
            $options['serviceName'] = 'Test';
        }

        $config = new Config($options);

        $this->assertEquals($optionValue, $config->$preferredOption());
    }

    public function deprecatedOptionsChecks(): array
    {
        return [
            'active/enabled' => ['active', true, 'enabled'],
            'appName/serviceName' => ['appName', 'My App', 'serviceName'],
            'appVersion/serviceVersion' => ['appVersion', '1.0', 'serviceVersion'],
            'backtraceLimit/stackTraceLimit' => ['backtraceLimit', 10, 'stackTraceLimit'],
        ];
    }

    public function testLoggingConfigValuesMasksSecretToken(): void
    {
        $logger = new TestLogger();

        new Config(['serviceName' => 'Test', 'secretToken' => 'abc123xyz', 'logger' => $logger]);

        $this->assertTrue($logger->hasDebugThatMatches('/"secretToken":"a\*\*\*z"/'));
    }

    /**
     * @throws ConfigurationException
     * @throws UnsupportedConfigurationValueException
     * @throws \Nipwaayoni\Exception\MissingServiceNameException
     *
     * @dataProvider activeAndEnabledChecks
     */
    public function testHandlesActiveAndEnabledSettings(array $settings, bool $expected): void
    {
        $enabledEnvValue = $settings['enabledEnvValue'];
        $activeAppValue = $settings['activeAppValue'];
        $enabledAppValue = $settings['enabledAppValue'];

        $configValues = ['serviceName' => 'Test App'];

        if (null !== $enabledEnvValue) {
            putenv('ELASTIC_APM_ENABLED=' . $enabledEnvValue);
        }

        if (null !== $activeAppValue) {
            $configValues['active'] = $activeAppValue;
        }

        if (null !== $enabledAppValue) {
            $configValues['enabled'] = $enabledAppValue;
        }

        $config = new Config($configValues);

        $this->assertEquals($expected, $config->get('active'));
        $this->assertEquals($expected, $config->get('enabled'));
        $this->assertEquals($expected, $config->enabled());
    }

    public function activeAndEnabledChecks(): array
    {
        return [
            'default' => [
                [
                    'enabledEnvValue' => null,
                    'activeAppValue' => null,
                    'enabledAppValue' => null,
                ],
                true
            ],
            'env enabled only' => [
                [
                    'enabledEnvValue' => 'true',
                    'activeAppValue' => null,
                    'enabledAppValue' => null,
                ],
                true
            ],
            'env not enabled only' => [
                [
                    'enabledEnvValue' => 'false',
                    'activeAppValue' => null,
                    'enabledAppValue' => null,
                ],
                false
            ],
            'app enabled only' => [
                [
                    'enabledEnvValue' => null,
                    'activeAppValue' => null,
                    'enabledAppValue' => true,
                ],
                true
            ],
            'app not enabled only' => [
                [
                    'enabledEnvValue' => null,
                    'activeAppValue' => null,
                    'enabledAppValue' => false,
                ],
                false
            ],
            'app active only' => [
                [
                    'enabledEnvValue' => null,
                    'activeAppValue' => true,
                    'enabledAppValue' => null,
                ],
                true
            ],
            'app not active only' => [
                [
                    'enabledEnvValue' => null,
                    'activeAppValue' => false,
                    'enabledAppValue' => null,
                ],
                false
            ],
        ];
    }

    public function testSupportsLegacyAppNameOption(): void
    {
        $config = new Config(['appName' => 'My App']);

        $this->assertEquals('My App', $config->serviceName());
    }

    public function testSupportsLegacyAppVersionOption(): void
    {
        $config = new Config(['appName' => 'My App', 'appVersion' => '1.5']);

        $this->assertEquals('1.5', $config->serviceVersion());
    }

    public function testSupportsLegacyBacktraceLimitOption(): void
    {
        $config = new Config(['appName' => 'My App', 'backtraceLimit' => 10]);

        $this->assertEquals(10, $config->stackTraceLimit());
    }
}
