<?php

namespace Nipwaayoni\Tests\Factory;

use Nipwaayoni\Config;
use Nipwaayoni\Factory\ConnectorFactory;
use PHPUnit\Framework\TestCase;

class ConnectorFactoryTest extends TestCase
{
    /**
     * @var ConnectorFactory
     */
    private $factory;

    public function setUp(): void
    {
        parent::setUp();

        $this->factory = new ConnectorFactory();
    }

    public function testCreatesNullCredentialsWhenNeitherTokenOrApiKeyArePresent(): void
    {
        $credentials = $this->factory->makeCredential($this->makeConfig());

        $this->assertFalse($credentials->includeAuthorizationHeader());
    }

    /**
     * @dataProvider credentialChecks
     */
    public function testCreatesCorrectCredentialsFromProvidedConfig(array $options, string $headerValue): void
    {
        $credentials = $this->factory->makeCredential($this->makeConfig($options));

        $this->assertTrue($credentials->includeAuthorizationHeader());
        $this->assertEquals($headerValue, $credentials->authorizationHeaderValue());
    }

    public function credentialChecks(): array
    {
        return [
            'secret token' => [['secretToken' => 'abc123'], 'Bearer: abc123'],
            'api key' => [['apiKey' => 'xyz456'], 'ApiKey: xyz456'],
            'token and api key' => [['secretToken' => 'abc123', 'apiKey' => 'xyz456'], 'ApiKey: xyz456'],
        ];
    }

    private function makeConfig(array $overrides = []): Config
    {
        $options = array_merge([
            'serviceName'     => sprintf('app_name_%d', rand(10, 99)),
            'secretToken'     => null,
            'apiKey'          => null,
            'serverUrl'       => sprintf('https://node%d.domain.tld:%d', rand(10, 99), rand(1000, 9999)),
            'serviceVersion'  => sprintf('%d.%d.42', rand(0, 3), rand(0, 10)),
            'frameworkName'   => uniqid(),
            'timeout'         => rand(10, 20),
            'hostname'        => sprintf('host_%d', rand(0, 9)),
            'enabled'         => false,
        ], $overrides);

        return new Config($options);

    }
}
