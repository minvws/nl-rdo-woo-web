<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\WooIndex\Builder;

use Shared\Domain\WooIndex\Producer\InvalidChunkSizeException;
use Shared\Domain\WooIndex\WooIndexRunOptions;
use Shared\Tests\Unit\UnitTestCase;

final class WooIndexRunOptionsTest extends UnitTestCase
{
    public function testCreatingOptionsWithInvalidChunkSizeThrowsException(): void
    {
        $this->expectExceptionObject(InvalidChunkSizeException::create());

        new WooIndexRunOptions(
            chunkSize: WooIndexRunOptions::MAX_CHUNK_SIZE + 1
        );
    }
}
