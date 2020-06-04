<?php


namespace Nipwaayoni\Contexts;


use Nipwaayoni\Exception\Contexts\UnsupportedContextKeyException;

class ContextCollection
{
    private $allowed = [
        'user',
        'custom',
        'tags',
        'env',
        'cookies',
    ];

    private $contexts = [];
    
    public function __construct(array $contexts)
    {
        $this->validateContexts($contexts);
        $this->setContexts($contexts);
    }

    private function validateContexts(array $contexts): void
    {
        $unknownKeys = array_diff(array_keys($contexts), $this->allowed);

        if (empty($unknownKeys)) {
            return;
        }

        throw new UnsupportedContextKeyException(implode('|', $unknownKeys));
    }

    private function setContexts(array $contexts): void
    {
        foreach ($this->allowed as $key) {
            $this->contexts[$key] = $contexts[$key] ?? [];
        }
    }

    public function user(): array
    {
        return $this->contexts['user'];
    }

    public function custom(): array
    {
        return $this->contexts['custom'];
    }

    public function tags(): array
    {
        return $this->contexts['tags'];
    }

    public function env(): array
    {
        return $this->contexts['env'];
    }

    public function cookies(): array
    {
        return $this->contexts['cookies'];
    }

    public function toArray(): array
    {
        return $this->contexts;
    }

    public function merge(ContextCollection $context): self
    {
        return new self(array_replace_recursive($this->contexts, $context->toArray()));
    }
}