<?php

namespace Nipwaayoni\Tests\Helper;

use Nipwaayoni\Agent;
use Nipwaayoni\Exception\ConfigurationException;
use Nipwaayoni\Exception\Helper\UnsupportedConfigurationValueException;
use Nipwaayoni\Config;
use Nipwaayoni\Tests\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;

/**
 * Test Case for @see \Nipwaayoni\Config
 */
final class ConfigTest extends TestCase
{

  /**
   * @covers \Nipwaayoni\Config::__construct
   * @covers \Nipwaayoni\Agent::getConfig
   * @covers \Nipwaayoni\Config::getDefaultConfig
   * @covers \Nipwaayoni\Config::asArray
   */
    public function testControlDefaultConfig()
    {
        $appName = sprintf('app_name_%d', rand(10, 99));
        $config = (new Config([ 'appName' => $appName, 'active' => false, ]))->asArray();

        $this->assertArrayHasKey('appName', $config);
        $this->assertArrayHasKey('secretToken', $config);
        $this->assertArrayHasKey('serverUrl', $config);
        $this->assertArrayHasKey('hostname', $config);
        $this->assertArrayHasKey('active', $config);
        $this->assertArrayHasKey('timeout', $config);
        $this->assertArrayHasKey('appVersion', $config);
        $this->assertArrayHasKey('environment', $config);
        $this->assertArrayHasKey('backtraceLimit', $config);
        $this->assertArrayHasKey('transactionSampleRate', $config);

        $this->assertEquals($appName, $config['appName']);
        $this->assertNull($config['secretToken']);
        $this->assertEquals('http://127.0.0.1:8200', $config['serverUrl']);
        $this->assertEquals(gethostname(), $config['hostname']);
        $this->assertFalse($config['active']);
        $this->assertEquals(10, $config['timeout']);
        $this->assertEquals('development', $config['environment']);
        $this->assertEquals(0, $config['backtraceLimit']);
        $this->assertEquals(1, $config['transactionSampleRate']);
    }

    /**
     * @depends testControlDefaultConfig
     *
     * @covers \Nipwaayoni\Config::__construct
     * @covers \Nipwaayoni\Agent::getConfig
     * @covers \Nipwaayoni\Config::getDefaultConfig
     * @covers \Nipwaayoni\Config::asArray
     */
    public function testControlInjectedConfig()
    {
        $init = [
            'appName'       => sprintf('app_name_%d', rand(10, 99)),
            'secretToken'   => hash('tiger128,3', time()),
            'serverUrl'     => sprintf('https://node%d.domain.tld:%d', rand(10, 99), rand(1000, 9999)),
            'appVersion'    => sprintf('%d.%d.42', rand(0, 3), rand(0, 10)),
            'frameworkName' => uniqid(),
            'timeout'       => rand(10, 20),
            'hostname'      => sprintf('host_%d', rand(0, 9)),
            'active'        => false,
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
     * @covers \Nipwaayoni\Config::getDefaultConfig
     * @covers \Nipwaayoni\Config::get
     */
    public function testGetConfig()
    {
        $init = [
            'appName' => sprintf('app_name_%d', rand(10, 99)),
            'active'  => false,
        ];

        $config = new Config($init);

        $this->assertEquals($config->get('appName'), $init['appName']);
    }

    /**
     * @depends testControlDefaultConfig
     *
     * @covers \Nipwaayoni\Config::__construct
     * @covers \Nipwaayoni\Agent::getConfig
     * @covers \Nipwaayoni\Config::getDefaultConfig
     * @covers \Nipwaayoni\Config::asArray
     */
    public function testTrimElasticServerUrl()
    {
        $init = [
            'serverUrl' => 'http://foo.bar/',
            'appName' => sprintf('app_name_%d', rand(10, 99)),
            'active'  => false,
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
     * @throws \Nipwaayoni\Exception\MissingAppNameException
     *
     * @dataProvider unsupportedConfigOptions
     */
    public function testThrowsExceptionIfUnsupportedOptionIsIncluded(string $option): void
    {
        $this->expectException(UnsupportedConfigurationValueException::class);

        new Config([
            'appName' => 'Test',
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

    public function testSetsAppNameFromEnvironmentVariable(): void
    {
        putenv(sprintf('%s=%s', 'ELASTIC_APM_APP_NAME', 'My Test App'));

        $config = new Config();

        $this->assertEquals('My Test App', $config->get('appName'));
    }

    public function testExplicitSettingTakesPrecedenceOverEnvironmentVariable(): void
    {
        putenv(sprintf('%s=%s', 'ELASTIC_APM_APP_NAME', 'My Test App'));

        $config = new Config(['appName' => 'Test']);

        $this->assertEquals('Test', $config->get('appName'));
    }

    /**
     * @throws UnsupportedConfigurationValueException
     * @throws \Nipwaayoni\Exception\MissingAppNameException
     *
     * @dataProvider environmentVariableChecks
     */
    public function testAllowsSettingOptionsWithEnvironmentVariables(string $envName, string $envValue, string $configName, $configValue): void
    {
        $envFullName = 'ELASTIC_APM_' . strtoupper($envName);

        putenv(sprintf('%s=%s', $envFullName, $envValue));

        $config = new Config(['appName' => 'Test']);

        $this->assertEquals($configValue, $config->get($configName));

        putenv(sprintf('%s=', $envFullName));
    }

    public function environmentVariableChecks(): array
    {
        // App Name is tested separately
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
                'example.com',
                'hostname',
                'example.com',
            ],
            'app version' => [
                'app_version',
                '1.2',
                'appVersion',
                '1.2',
            ],
            'enabled (active)' => [
                'enabled',
                'false',
                'active',
                false,
            ],
            'not enabled (active)' => [
                'enabled',
                'true',
                'active',
                true,
            ],
            'enabled (enabled)' => [
                'enabled',
                'false',
                'enabled',
                false,
            ],
            'not enabled (enabled)' => [
                'enabled',
                'true',
                'enabled',
                true,
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
                'backtrace_limit',
                '10',
                'backtraceLimit',
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

    public function testThrowsExceptionWhenConfigArrayContainsActiveAndEnabled(): void
    {
        $this->expectException(ConfigurationException::class);

        new Config(['active' => true, 'enabled' => true]);
    }

    public function testLogsNoticeWhenUsingActiveInsteadOfEnabled(): void
    {
        $logger = new TestLogger();

        new Config(['appName' => 'Test', 'active' => true, 'logger' => $logger]);

        $this->assertTrue($logger->hasNoticeThatContains('The "active" configuration option is deprecated, please use "enabled" instead.'));
    }

    public function testLoggingConfigValuesMasksSecretToken(): void
    {
        $logger = new TestLogger();

        new Config(['appName' => 'Test', 'secretToken' => 'abc123xyz', 'logger' => $logger]);

        $this->assertTrue($logger->hasDebugThatMatches('/secretToken=a\*\*\*z/'));
    }
}
