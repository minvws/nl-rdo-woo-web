<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier;

use Shared\Domain\Publication\Dossier\DocumentPrefix;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\WooUuid;
use Symfony\Component\Uid\Uuid;

final class DocumentPrefixTest extends UnitTestCase
{
    public function testDefaultId(): void
    {
        $prefix = $this->getFaker()->word();
        $documentPrefix = new DocumentPrefix($prefix);

        self::assertInstanceOf(WooUuid::class, $documentPrefix->getId());
    }

    public function testGivenId(): void
    {
        $prefix = $this->getFaker()->word();
        $id = Uuid::fromString($this->getFaker()->uuid());
        $documentPrefix = new DocumentPrefix($prefix, $id);

        self::assertSame($id, $documentPrefix->getId());
    }
}
