<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Security\Authorization;

use App\Service\Security\Authorization\AuthorizationEntryRequestStore;
use App\Service\Security\Authorization\Entry;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthorizationRequestStoreTest extends MockeryTestCase
{
    private RequestStack&Mockery\MockInterface $requestStack;
    private Request&Mockery\MockInterface $request;
    private AuthorizationEntryRequestStore $store;

    public function setUp(): void
    {
        $this->request = \Mockery::mock(Request::class);
        $this->request->attributes = new ParameterBag();

        $this->requestStack = \Mockery::mock(RequestStack::class);

        $this->store = new AuthorizationEntryRequestStore($this->requestStack);
    }

    public function testGetEntriesThrowsExceptionWhenThereIsNoRequest(): void
    {
        $this->requestStack->shouldReceive('getCurrentRequest')->andReturnNull();

        $this->expectException(\RuntimeException::class);

        $this->store->getEntries();
    }

    public function testGetEntriesThrowsExceptionsWhenAttributeIsNotSet(): void
    {
        $this->requestStack->shouldReceive('getCurrentRequest')->andReturn($this->request);

        $this->expectException(\RuntimeException::class);

        $this->store->getEntries();
    }

    public function testGetEntries(): void
    {
        $entries = [
            \Mockery::mock(Entry::class),
        ];

        $this->requestStack->shouldReceive('getCurrentRequest')->andReturn($this->request);
        $this->request->attributes->set('auth_matrix', $entries);

        $this->assertEquals($entries, $this->store->getEntries());
    }

    public function testStoreEntriesReturnsFalseWhenThereIsNoRequest(): void
    {
        $entries = [
            \Mockery::mock(Entry::class),
        ];

        $this->requestStack->shouldReceive('getCurrentRequest')->andReturnNull();

        $this->assertFalse($this->store->storeEntries(...$entries));
    }

    public function testStoreEntriesSetsRequestAttributeAndReturnsTrue(): void
    {
        $entries = [
            \Mockery::mock(Entry::class),
        ];

        $this->requestStack->shouldReceive('getCurrentRequest')->andReturn($this->request);

        $this->assertTrue($this->store->storeEntries(...$entries));
        $this->assertEquals(
            $entries,
            $this->request->attributes->get('auth_matrix'),
        );
    }
}
