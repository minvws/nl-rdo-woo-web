<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Service\Storage;

use Mockery;
use Mockery\Matcher\Closure as ClosureMatcher;
use Mockery\MockInterface;
use Shared\Service\Storage\HasAlive;
use Shared\Service\Storage\RemoteFilesystem;
use Shared\Service\Storage\StorageAliveInterface;
use Shared\Tests\Unit\UnitTestCase;

use function explode;
use function str_contains;
use function strlen;

final class HasAliveTest extends UnitTestCase
{
    private RemoteFilesystem&MockInterface $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = Mockery::mock(RemoteFilesystem::class);
    }

    public function testHasAlive(): void
    {
        $instance = $this->getInstance();

        $this->storage
            ->expects('write')
            ->with($this->locationMatcher($location), $this->hashMatcher($hash))
            ->andReturnTrue();
        $this->storage
            ->expects('read')
            ->andReturnUsing(function () use (&$hash) {
                return $hash;
            });
        $this->storage->expects('delete')->andReturnTrue();

        $this->assertTrue($instance->isAlive(), 'The storage is not alive');
    }

    public function testHasAliveWithNonMatchingContent(): void
    {
        $instance = $this->getInstance();

        $this->storage
            ->expects('write')
            ->with($this->locationMatcher($location), $this->hashMatcher($hash))
            ->andReturnTrue();
        $this->storage
            ->expects('read')
            ->andReturn('non-matching-content');
        $this->storage->expects('delete')->andReturnTrue();

        $this->assertFalse($instance->isAlive(), 'The storage is alive');
    }

    public function testIsAliveWithFailingWrite(): void
    {
        $instance = $this->getInstance();

        $this->storage
            ->expects('write')
            ->andReturnFalse();

        $this->assertFalse($instance->isAlive(), 'The storage is alive');
    }

    public function testIsAliveWithFailingRead(): void
    {
        $instance = $this->getInstance();

        $this->storage
            ->expects('write')
            ->with($this->locationMatcher($location), $this->hashMatcher($hash))
            ->andReturnTrue();
        $this->storage
            ->expects('read')
            ->andReturnFalse();

        $this->assertFalse($instance->isAlive(), 'The storage is alive');
    }

    public function testIsAliveWithFailingDelete(): void
    {
        $instance = $this->getInstance();

        $this->storage
            ->expects('write')
            ->with($this->locationMatcher($location), $this->hashMatcher($hash))
            ->andReturnTrue();
        $this->storage
            ->expects('read')
            ->andReturnUsing(function () use (&$hash) {
                return $hash;
            });
        $this->storage->expects('delete')->andReturnFalse();

        $this->assertFalse($instance->isAlive(), 'The storage is alive');
    }

    private function getInstance(): StorageAliveInterface
    {
        return new class($this->storage) implements StorageAliveInterface {
            use HasAlive;

            public function __construct(
                private RemoteFilesystem $storage,
            ) {
            }

            protected function getStorage(): RemoteFilesystem
            {
                return $this->storage;
            }
        };
    }

    private function locationMatcher(?string &$locationRef): ClosureMatcher
    {
        return Mockery::on(function (string $location) use (&$locationRef): bool {
            $locationRef = $location;

            if (! str_contains($location, '.')) {
                return false;
            }

            [$prefix, $hash] = explode('.', $location, 2);

            return $prefix === 'healthcheck' && strlen($hash) === 64;
        });
    }

    private function hashMatcher(?string &$hashRef): ClosureMatcher
    {
        return Mockery::on(function (string $hash) use (&$hashRef): bool {
            $hashRef = $hash;

            return strlen($hash) === 64;
        });
    }
}
