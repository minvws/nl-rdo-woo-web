<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index;

use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Disposition\Disposition;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use App\Domain\Publication\Dossier\Type\WooDecision\Entity\WooDecision;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\IndexException;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

class ElasticDocumentTypeTest extends UnitTestCase
{
    /**
     * @return array<string, array{entity: AbstractDossier, expectedType: ElasticDocumentType}>
     */
    public static function fromEntityProvider(): array
    {
        return [
            'Covenant' => [
                'entity' => new Covenant(),
                'expectedType' => ElasticDocumentType::COVENANT,
            ],
            'WooDecision' => [
                'entity' => new WooDecision(),
                'expectedType' => ElasticDocumentType::WOO_DECISION,
            ],
            'AnnualReport' => [
                'entity' => new AnnualReport(),
                'expectedType' => ElasticDocumentType::ANNUAL_REPORT,
            ],
            'InvestigationReport' => [
                'entity' => new InvestigationReport(),
                'expectedType' => ElasticDocumentType::INVESTIGATION_REPORT,
            ],
            'Disposition' => [
                'entity' => new Disposition(),
                'expectedType' => ElasticDocumentType::DISPOSITION,
            ],
        ];
    }

    #[DataProvider('fromEntityProvider')]
    public function testFromEntityForCovenant(object $entity, ElasticDocumentType $expectedType): void
    {
        self::assertEquals(
            $expectedType,
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

    public function testTransForMainType(): void
    {
        $locale = 'en_GB';
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator
            ->expects('trans')
            ->with('public.documents.type.' . ElasticDocumentType::COVENANT->value, [], null, $locale)
            ->andReturn('foo');

        self::assertEquals(
            'foo',
            ElasticDocumentType::COVENANT->trans($translator, $locale),
        );
    }

    public function testTransForSubType(): void
    {
        $locale = 'en_GB';
        $translator = \Mockery::mock(TranslatorInterface::class);
        $translator
            ->expects('trans')
            ->with('public.search.type.' . ElasticDocumentType::COVENANT_MAIN_DOCUMENT->value, [], null, $locale)
            ->andReturn('foo');

        self::assertEquals(
            'foo',
            ElasticDocumentType::COVENANT_MAIN_DOCUMENT->trans($translator, $locale),
        );
    }
}
