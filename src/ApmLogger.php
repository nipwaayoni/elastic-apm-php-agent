<?php


namespace Nipwaayoni;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class ApmLogger extends \Psr\Log\AbstractLogger
{
    /** @var LoggerInterface */
    private $logger;

    /** @var string */
    private $level;

    /** @var int  */
    private $levelValue;

    private $levels = [
        'emergency' => 0,
        'alert' => 1,
        'critical' => 2,
        'error' => 3,
        'warning' => 4,
        'notice' => 5,
        'info' => 6,
        'debug' => 7,
    ];

    public function __construct(LoggerInterface $logger = null, string $level = LogLevel::INFO)
    {
        $this->logger = $logger ?? new NullLogger();
        $this->level = $level;
        $this->levelValue = $this->getLevelValue($level);
    }

    private function getLevelValue(string $level): int
    {
        return $this->levels[strtolower($level)];
    }

    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = array())
    {
        if ($this->doNotLog($level)) {
            return;
        }

        $this->logger->log($level, $message, $context);
    }

    private function doNotLog(string $level): bool
    {
        return $this->getLevelValue($level) > $this->levelValue;
    }
}
