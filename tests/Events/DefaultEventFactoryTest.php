<?php

namespace Nipwaayoni\Tests\Events;

use Nipwaayoni\Events\DefaultEventFactory;
use Nipwaayoni\Events\SampleStrategy;
use Nipwaayoni\Tests\TestCase;

class DefaultEventFactoryTest extends TestCase
{
    public function testAppliesDefaultSamplingStrategyToTransactions(): void
    {
        $factory = new DefaultEventFactory();

        $transaction = $factory->newTransaction('My Transaction', []);

        $this->assertTrue($transaction->includeSamples());
    }

    /**
     * @dataProvider samplingStrategyChecks
     */
    public function testAppliesSamplingStrategyToNewTransactions(bool $strategyType, bool $expected): void
    {
        $strategy = $this->makeSampleStrategy($strategyType);

        $factory = new DefaultEventFactory();
        $factory->setTransactionSampleStrategy($strategy);

        $transaction = $factory->newTransaction('My Transaction', []);

        $this->assertEquals($expected, $transaction->includeSamples());
    }
    public static function samplingStrategyChecks(): array
    {
        return [
            'include' => [true, true],
            'exclude' => [false, false],
        ];
    }
}
