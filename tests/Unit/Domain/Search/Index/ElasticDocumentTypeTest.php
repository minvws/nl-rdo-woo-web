<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index;

use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Covenant\CovenantAttachment;
use App\Domain\Publication\Dossier\Type\Disposition\Disposition;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\IndexException;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ElasticDocumentTypeTest extends MockeryTestCase
{
    public function testFromEntityForCovenant(): void
    {
        $entity = \Mockery::mock(Covenant::class);
        $entity->shouldReceive('getType')->andReturn(DossierType::COVENANT);

        self::assertEquals(
            ElasticDocumentType::COVENANT,
            ElasticDocumentType::fromEntity($entity),
        );
    }

    public function testFromEntityForWooDecision(): void
    {
        $entity = \Mockery::mock(WooDecision::class);
        $entity->shouldReceive('getType')->andReturn(DossierType::WOO_DECISION);

        self::assertEquals(
            ElasticDocumentType::WOO_DECISION,
            ElasticDocumentType::fromEntity($entity),
        );
    }

    public function testFromEntityForAnnualReport(): void
    {
        $entity = \Mockery::mock(AnnualReport::class);
        $entity->shouldReceive('getType')->andReturn(DossierType::ANNUAL_REPORT);

        self::assertEquals(
            ElasticDocumentType::ANNUAL_REPORT,
            ElasticDocumentType::fromEntity($entity),
        );
    }

    public function testFromEntityForInvestigationReport(): void
    {
        $entity = \Mockery::mock(InvestigationReport::class);
        $entity->shouldReceive('getType')->andReturn(DossierType::INVESTIGATION_REPORT);

        self::assertEquals(
            ElasticDocumentType::INVESTIGATION_REPORT,
            ElasticDocumentType::fromEntity($entity),
        );
    }

    public function testFromEntityForDisposition(): void
    {
        $entity = \Mockery::mock(Disposition::class);
        $entity->shouldReceive('getType')->andReturn(DossierType::DISPOSITION);

        self::assertEquals(
            ElasticDocumentType::DISPOSITION,
            ElasticDocumentType::fromEntity($entity),
        );
    }

    public function testFromEntityForCovenantAttachment(): void
    {
        $entity = \Mockery::mock(CovenantAttachment::class);

        self::assertEquals(
            ElasticDocumentType::ATTACHMENT,
            ElasticDocumentType::fromEntity($entity),
        );
    }

    public function testFromEntityForUnmappedClass(): void
    {
        $entity = new \stdClass();

        $this->expectException(IndexException::class);
        ElasticDocumentType::fromEntity($entity);
    }

    public function testGetMainTypeValues(): void
    {
        self::assertContains(
            ElasticDocumentType::COVENANT->value,
            ElasticDocumentType::getMainTypeValues(),
        );
    }

    public function testGetSubTypeValues(): void
    {
        self::assertContains(
            ElasticDocumentType::ATTACHMENT->value,
            ElasticDocumentType::getSubTypeValues(),
        );
    }
}
