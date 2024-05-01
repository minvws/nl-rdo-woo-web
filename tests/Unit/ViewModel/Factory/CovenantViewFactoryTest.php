<?php

declare(strict_types=1);

namespace App\Tests\Unit\ViewModel\Factory;

use App\Domain\Publication\Dossier\DossierStatus;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Tests\Unit\UnitTestCase;
use App\ViewModel\Factory\CovenantViewFactory;
use Symfony\Component\Uid\Uuid;

final class CovenantViewFactoryTest extends UnitTestCase
{
    public function testMake(): void
    {
        $uuid = \Mockery::mock(Uuid::class);
        $uuid->shouldReceive('toRfc4122')->andReturn($expectedUuid = 'my uuid');

        $covenant = \Mockery::mock(Covenant::class);
        $covenant->shouldReceive('getTitle')->andReturn($expectedTitle = 'my title');
        $covenant->shouldReceive('getPublicationDate')->andReturn($expectedPublicationDate = \DateTimeImmutable::createFromInterface($this->getFaker()->dateTimeBetween('-2 years')));
        $covenant->shouldReceive('getId')->andReturn($uuid);
        $covenant->shouldReceive('getDossierNr')->andReturn($expectedDossierNr = 'my dossier nr');
        $covenant->shouldReceive('getStatus')->andReturn($expectedStatus = DossierStatus::PUBLISHED);
        $covenant->shouldReceive('getSummary')->andReturn($expectedSummary = 'my summary');

        $result = (new CovenantViewFactory())->make($covenant);

        $this->assertSame($covenant, $result->entity);
        $this->assertSame($expectedUuid, $result->dossierId);
        $this->assertSame($expectedDossierNr, $result->dossierNr);
        $this->assertSame($expectedStatus->isPreview(), $result->isPreview);
        $this->assertSame($expectedTitle, $result->title);
        $this->assertSame($expectedTitle, $result->pageTitle);
        $this->assertSame($expectedPublicationDate, $result->publicationDate);
        $this->assertSame($expectedSummary, $result->summary);
    }
}
