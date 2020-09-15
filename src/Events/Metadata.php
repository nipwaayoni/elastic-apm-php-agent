<?php

namespace Nipwaayoni\Events;

use Nipwaayoni\Agent;
use Nipwaayoni\Config;
use Nipwaayoni\Helper\Encoding;

/**
 *
 * Metadata Event
 *
 * @link https://www.elastic.co/guide/en/apm/server/7.3/metadata-api.html
 *
 */
class Metadata extends EventBean implements \JsonSerializable
{
    protected $eventType = 'metadata';

    /**
     * @var Config
     */
    private $config;
    /**
     * @var array
     */
    private $agentMetaData;

    /**
     * @param array $contexts
     * @param Config $config
     */
    public function __construct(array $contexts, Config $config, array $agentMetaData)
    {
        parent::__construct($contexts);
        $this->config = $config;
        $this->agentMetaData = $agentMetaData;
    }

    /**
     * Generate request data
     *
     * @return array
     */
    final public function jsonSerialize(): array
    {
        return [
            $this->eventType => [
                'service' => [
                    'name'    => Encoding::keywordField($this->config->appName()),
                    'version' => Encoding::keywordField($this->config->appVersion()),
                    'framework' => [
                        'name' => $this->config->framework(),
                        'version' => $this->config->frameworkVersion(),
                    ],
                    'language' => [
                        'name'    => 'php',
                        'version' => phpversion()
                    ],
                    'process' => [
                        'pid' => getmypid(),
                    ],
                    'agent' => $this->agentMetaData,
                    'environment' => Encoding::keywordField($this->config->environment())
                ],
                'system' => [
                    'hostname'     => Encoding::keywordField($this->config->hostname()),
                    'architecture' => php_uname('m'),
                    'platform'     => php_uname('s')
                ]
            ]
        ];
    }
}
