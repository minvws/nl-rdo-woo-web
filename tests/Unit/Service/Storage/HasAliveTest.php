<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\Storage;

use App\Service\Storage\HasAlive;
use App\Service\Storage\RemoteFilesystem;
use App\Service\Storage\StorageAliveInterface;
use App\Tests\Unit\UnitTestCase;
use Mockery\Matcher\Closure as ClosureMatcher;
use Mockery\MockInterface;

final class HasAliveTest extends UnitTestCase
{
    private RemoteFilesystem&MockInterface $storage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storage = \Mockery::mock(RemoteFilesystem::class);
    }

    public function testHasAlive(): void
    {
        $instance = $this->getInstance();

        $this->storage
            ->shouldReceive('write')
            ->once()
            ->with($this->locationMatcher($location), $this->hashMatcher($hash))
            ->andReturnTrue();
        $this->storage
            ->shouldReceive('read')
            ->once()
            ->andReturnUsing(function () use (&$hash) { return $hash; });
        $this->storage->shouldReceive('delete')->once()->andReturnTrue();

        $this->assertTrue($instance->isAlive(), 'The storage is not alive');
    }

    public function testHasAliveWithNonMatchingContent(): void
    {
        $instance = $this->getInstance();

        $this->storage
            ->shouldReceive('write')
            ->once()
            ->with($this->locationMatcher($location), $this->hashMatcher($hash))
            ->andReturnTrue();
        $this->storage
            ->shouldReceive('read')
            ->once()
            ->andReturn('non-matching-content');
        $this->storage->shouldReceive('delete')->once()->andReturnTrue();

        $this->assertFalse($instance->isAlive(), 'The storage is alive');
    }

    public function testIsAliveWithFailingWrite(): void
    {
        $instance = $this->getInstance();

        $this->storage
            ->shouldReceive('write')
            ->once()
            ->andReturnFalse();

        $this->assertFalse($instance->isAlive(), 'The storage is alive');
    }

    public function testIsAliveWithFailingRead(): void
    {
        $instance = $this->getInstance();

        $this->storage
            ->shouldReceive('write')
            ->once()
            ->with($this->locationMatcher($location), $this->hashMatcher($hash))
            ->andReturnTrue();
        $this->storage
            ->shouldReceive('read')
            ->once()
            ->andReturnFalse();

        $this->assertFalse($instance->isAlive(), 'The storage is alive');
    }

    public function testIsAliveWithFailingDelete(): void
    {
        $instance = $this->getInstance();

        $this->storage
            ->shouldReceive('write')
            ->once()
            ->with($this->locationMatcher($location), $this->hashMatcher($hash))
            ->andReturnTrue();
        $this->storage
            ->shouldReceive('read')
            ->once()
            ->andReturnUsing(function () use (&$hash) { return $hash; });
        $this->storage->shouldReceive('delete')->once()->andReturnFalse();

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
        return \Mockery::on(function (string $location) use (&$locationRef): bool {
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
        return \Mockery::on(function (string $hash) use (&$hashRef): bool {
            $hashRef = $hash;

            return strlen($hash) === 64;
        });
    }
}
