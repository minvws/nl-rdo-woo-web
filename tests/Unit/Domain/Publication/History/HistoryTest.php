<?php

declare(strict_types=1);

namespace Shared\Tests\Unit\Domain\Publication\History;

use DateTimeImmutable;
use Shared\Domain\Publication\History\History;
use Shared\Tests\Unit\UnitTestCase;
use Symfony\Component\Uid\Uuid;

class HistoryTest extends UnitTestCase
{
    public function testSetAndGetType(): void
    {
        $entity = new History();
        $entity->setType($type = 'foo');

        $this->assertEquals($type, $entity->getType());
    }

    public function testSetAndGetIdentifier(): void
    {
        $entity = new History();
        $entity->setIdentifier($id = Uuid::v6());

        $this->assertEquals($id, $entity->getIdentifier());
    }

    public function testSetAndGetCreatedDt(): void
    {
        $entity = new History();
        $entity->setCreatedDt($date = new DateTimeImmutable());

        $this->assertEquals($date, $entity->getCreatedDt());
    }

    public function testSetAndGetSite(): void
    {
        $entity = new History();
        $entity->setSite($site = 'foo');

        $this->assertEquals($site, $entity->getSite());
    }
}
