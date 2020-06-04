<?php

namespace Nipwaayoni\Tests\Contexts;

use Nipwaayoni\Exception\Contexts\UnsupportedContextKeyException;
use Nipwaayoni\Contexts\ContextCollection;
use Nipwaayoni\Tests\TestCase;

class ContextCollectionTest extends TestCase
{
    private $data = [
        'user' => ['username' => 'bob'],
        'custom' => ['my-key' => 'some value'],
        'tags' => ['my-tag' => 'some value'],
        'env' => ['my-env' => 'some value'],
        'cookies' => ['my-cookie' => 'some value'],
    ];


    public function testCreatesEmptySharedUserContextByDefault(): void
    {
        $collection = new ContextCollection([]);

        $this->assertEmpty($collection->user());
    }

    public function testCreatesEmptySharedCustomContextByDefault(): void
    {
        $collection = new ContextCollection([]);

        $this->assertEmpty($collection->custom());
    }

    public function testCreatesEmptySharedTagsByDefault(): void
    {
        $collection = new ContextCollection([]);

        $this->assertEmpty($collection->tags());
    }

    public function testCreatesEmptySharedEnvByDefault(): void
    {
        $collection = new ContextCollection([]);

        $this->assertEmpty($collection->env());
    }

    public function testCreatesEmptySharedCookiesByDefault(): void
    {
        $collection = new ContextCollection([]);

        $this->assertEmpty($collection->cookies());
    }

    public function testCreatesSharedUserContextFromData(): void
    {
        $collection = new ContextCollection(['user' => $this->data['user']]);

        $this->assertEquals($this->data['user'], $collection->user());
    }

    public function testCreatesSharedCustomContextFromData(): void
    {
        $collection = new ContextCollection(['custom' => $this->data['custom']]);

        $this->assertEquals($this->data['custom'], $collection->custom());
    }

    public function testCreatesSharedTagsFromData(): void
    {
        $collection = new ContextCollection(['tags' => $this->data['tags']]);

        $this->assertEquals($this->data['tags'], $collection->tags());
    }

    public function testCreatesSharedEnvFromData(): void
    {
        $collection = new ContextCollection(['env' => $this->data['env']]);

        $this->assertEquals($this->data['env'], $collection->env());
    }

    public function testCreatesSharedCookiesFromData(): void
    {
        $collection = new ContextCollection(['cookies' => $this->data['cookies']]);

        $this->assertEquals($this->data['cookies'], $collection->cookies());
    }

    public function testCanBeConvertedToArray(): void
    {
        $collection = new ContextCollection($this->data);

        $this->assertEquals($this->data, $collection->toArray());
    }

    public function testMergesSharedContextWithProvidedContext(): void
    {
        $collection = new ContextCollection($this->data);
        $additionalCustomContext = ['my-other-key' => 'another value'];

        $localContext = $collection->merge(new ContextCollection(['custom' => $additionalCustomContext]));

        $expectedContext = $this->data;
        $expectedContext['custom'] = array_merge($additionalCustomContext, $expectedContext['custom']);

        $this->assertEquals($expectedContext, $localContext->toArray());
    }

    public function testThrowsExceptionForUnsupportedContext(): void
    {
        $this->expectException(UnsupportedContextKeyException::class);

        new ContextCollection(['extra' => []]);
    }
}
