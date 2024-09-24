<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Ingest\Content;

use App\Domain\Ingest\Content\LazyFileReference;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery\MockInterface;
use Mockery\VerificationDirector;

class LazyFileReferenceTest extends MockeryTestCase
{
    public function testLazyFileReferenceLoadsFileOnlyWhenNeededAndOnlyOnce(): void
    {
        /** @var callable&MockInterface $spy */
        $spy = spy(
            static function (): string {
                return '/foo/bar.txt';
            }
        );

        $reference = new LazyFileReference($spy);

        $spy->shouldNotHaveBeenCalled();

        self::assertEquals('/foo/bar.txt', $reference->getPath());

        /** @var VerificationDirector $verfication */
        $verfication = $spy->shouldHaveBeenCalled();
        $verfication->once();

        self::assertEquals('/foo/bar.txt', $reference->getPath());

        /** @var VerificationDirector $verfication */
        $verfication = $spy->shouldHaveBeenCalled();
        $verfication->once();
    }
}
