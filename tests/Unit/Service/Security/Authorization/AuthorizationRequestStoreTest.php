<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Security\Authorization;

use Mockery\MockInterface;
use Shared\Service\Security\Authorization\AuthorizationEntryRequestStore;
use Shared\Service\Security\Authorization\Entry;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthorizationRequestStoreTest extends UnitTestCase
{
    private RequestStack&MockInterface $requestStack;
    private Request&MockInterface $request;
    private AuthorizationEntryRequestStore $store;

    protected function setUp(): void
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

        self::assertEquals($entries, $this->store->getEntries());
    }

    public function testStoreEntriesReturnsFalseWhenThereIsNoRequest(): void
    {
        $entries = [
            \Mockery::mock(Entry::class),
        ];

        $this->requestStack->shouldReceive('getCurrentRequest')->andReturnNull();

        self::assertFalse($this->store->storeEntries(...$entries));
    }

    public function testStoreEntriesSetsRequestAttributeAndReturnsTrue(): void
    {
        $entries = [
            \Mockery::mock(Entry::class),
        ];

        $this->requestStack->shouldReceive('getCurrentRequest')->andReturn($this->request);

        self::assertTrue($this->store->storeEntries(...$entries));
        self::assertEquals(
            $entries,
            $this->request->attributes->get('auth_matrix'),
        );
    }
}
