<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier;

use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DocumentPrefix;
use Shared\Tests\Unit\UnitTestCase;

class DocumentPrefixTest extends UnitTestCase
{
    public function testSetPrefixAppliesUppercase(): void
    {
        $entity = new DocumentPrefix();
        $entity->setPrefix('foo');

        $this->assertEquals('FOO', $entity->getPrefix());
    }

    public function testSetPrefixCanOnlyBeUsedOnce(): void
    {
        $entity = new DocumentPrefix();
        $entity->setPrefix('foo');

        $this->expectException(\RuntimeException::class);
        $entity->setPrefix('bar');
    }

    public function testGetAndSetOrganisation(): void
    {
        $organisation = \Mockery::mock(Organisation::class);

        $entity = new DocumentPrefix();
        $entity->setOrganisation($organisation);

        $this->assertEquals($organisation, $entity->getOrganisation());
    }

    public function testArchive(): void
    {
        $entity = new DocumentPrefix();

        self::assertFalse($entity->isArchived());

        $entity->archive();

        $this->assertTrue($entity->isArchived());
    }

    public function testIssetPrefix(): void
    {
        $documentPrefix = new DocumentPrefix();

        self::assertFalse($documentPrefix->issetPrefix());

        $documentPrefix->setPrefix('foo');

        self::assertTrue($documentPrefix->issetPrefix());
    }
}
