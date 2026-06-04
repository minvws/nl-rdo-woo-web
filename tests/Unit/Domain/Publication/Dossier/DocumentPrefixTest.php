<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\Dossier;

use Mockery;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DocumentPrefix;
use Shared\Tests\Unit\UnitTestCase;

class DocumentPrefixTest extends UnitTestCase
{
    public function testSetPrefixAppliesUppercase(): void
    {
        $entity = new DocumentPrefix('foo');

        $this->assertEquals('FOO', $entity->getPrefix());
    }

    public function testGetAndSetOrganisation(): void
    {
        $organisation = Mockery::mock(Organisation::class);

        $entity = new DocumentPrefix('test');
        $entity->setOrganisation($organisation);

        $this->assertEquals($organisation, $entity->getOrganisation());
    }

    public function testArchive(): void
    {
        $entity = new DocumentPrefix('test');

        self::assertFalse($entity->isArchived());

        $entity->archive();

        $this->assertTrue($entity->isArchived());
    }
}
