<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\WooIndex\Builder;

use App\Domain\WooIndex\Producer\InvalidChunkSizeException;
use App\Domain\WooIndex\WooIndexRunOptions;
use App\Tests\Unit\UnitTestCase;

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
