<?php

namespace Nipwaayoni\Tests\Events;

use Nipwaayoni\Events\DefaultEventFactory;
use Nipwaayoni\Events\SamplingStrategy;
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
    public function testAppliesSamplingStrategyToNewTransactions(SamplingStrategy $strategy, bool $expected): void
    {
        $factory = new DefaultEventFactory();
        $factory->setTransactionSamplingStrategy($strategy);

        $transaction = $factory->newTransaction('My Transaction', []);

        $this->assertEquals($expected, $transaction->includeSamples());
    }
    public function samplingStrategyChecks(): array
    {
        return [
            'include' => [$this->makeIncludeStrategy(), true],
            'exclude' => [$this->makeExcludeStrategy(), false],
        ];
    }
}
