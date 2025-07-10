<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Publication\Dossier;

use App\Domain\Organisation\Organisation;
use App\Domain\Publication\Dossier\DocumentPrefix;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class DocumentPrefixTest extends MockeryTestCase
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
}
