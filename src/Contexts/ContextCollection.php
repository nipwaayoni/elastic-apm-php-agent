<?php

namespace Nipwaayoni\Contexts;

use Nipwaayoni\Exception\Contexts\UnsupportedContextKeyException;

class ContextCollection
{
    private $metadata = [
        'user' => [
            'default' => [],
            'mergeMethod' => 'array_replace_recursive',
        ],
        'custom' => [
            'default' => [],
            'mergeMethod' => 'array_replace_recursive',
        ],
        'tags' => [
            'default' => [],
            'mergeMethod' => 'array_replace_recursive',
        ],
        'env' => [
            'default' => ['SERVER_SOFTWARE'],
            'mergeMethod' => 'array_merge',
        ],
        'cookies' => [
            'default' => [],
            'mergeMethod' => 'array_merge',
        ],
    ];

    private $contexts = [];

    public function __construct(array $contexts)
    {
        $this->validateContexts($contexts);
        $this->setContexts($contexts);
    }

    private function validateContexts(array $contexts): void
    {
        $unknownKeys = array_diff(array_keys($contexts), $this->allowed());

        if (empty($unknownKeys)) {
            return;
        }

        throw new UnsupportedContextKeyException($unknownKeys);
    }

    private function allowed(): array
    {
        return array_keys($this->metadata);
    }

    private function default(string $key): array
    {
        return $this->metadata[$key]['default'];
    }

    private function mergeMethod(string $key): string
    {
        return $this->metadata[$key]['mergeMethod'];
    }

    private function setContexts(array $contexts): void
    {
        foreach ($this->allowed() as $key) {
            $this->contexts[$key] = $contexts[$key] ?? $this->default($key);
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
        $newContextData = $context->toArray();
        $mergedContextData = [];

        foreach ($this->allowed() as $key) {
            $mergeMethod = $this->mergeMethod($key);
            $mergedContextData[$key] = $mergeMethod($this->contexts[$key], $newContextData[$key]);
        }

        return new self($mergedContextData);
    }
}
