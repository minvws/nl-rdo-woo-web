<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Search\Index;

use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Attachment\Enum\AttachmentLanguage;
use App\Domain\Publication\Attachment\Enum\AttachmentType;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\Type\Advice\Advice;
use App\Domain\Publication\Dossier\Type\Advice\AdviceAttachment;
use App\Domain\Publication\Dossier\Type\Advice\AdviceMainDocument;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportAttachment;
use App\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use App\Domain\Publication\Dossier\Type\Covenant\Covenant;
use App\Domain\Publication\Dossier\Type\Disposition\Disposition;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionAttachment;
use App\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use App\Domain\Publication\Dossier\Type\DossierType;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportAttachment;
use App\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationAttachment;
use App\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use App\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use App\Domain\Publication\MainDocument\AbstractMainDocument;
use App\Domain\Search\Index\ElasticDocumentType;
use App\Domain\Search\Index\IndexException;
use App\Tests\Unit\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Contracts\Translation\TranslatorInterface;

class ElasticDocumentTypeTest extends UnitTestCase
{
    use MatchesSnapshots;

    /**
     * @return array<string, array{entity: AbstractDossier|AbstractAttachment|AbstractMainDocument, expectedType: ElasticDocumentType}>
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
            'OtherPublication' => [
                'entity' => new OtherPublication(),
                'expectedType' => ElasticDocumentType::OTHER_PUBLICATION,
            ],
            'OtherPublicationMainDocument' => [
                'entity' => new OtherPublicationMainDocument(
                    new OtherPublication(),
                    new \DateTimeImmutable(),
                    AttachmentType::ADVICE,
                    AttachmentLanguage::DUTCH,
                ),
                'expectedType' => ElasticDocumentType::OTHER_PUBLICATION_MAIN_DOCUMENT,
            ],
            'OtherPublicationAttachment' => [
                'entity' => new OtherPublicationAttachment(
                    new OtherPublication(),
                    new \DateTimeImmutable(),
                    AttachmentType::ADVICE,
                    AttachmentLanguage::DUTCH,
                ),
                'expectedType' => ElasticDocumentType::ATTACHMENT,
            ],
            'Advice' => [
                'entity' => new Advice(),
                'expectedType' => ElasticDocumentType::ADVICE,
            ],
            'AdviceMainDocument' => [
                'entity' => new AdviceMainDocument(
                    new Advice(),
                    new \DateTimeImmutable(),
                    AttachmentType::ADVICE,
                    AttachmentLanguage::DUTCH,
                ),
                'expectedType' => ElasticDocumentType::ADVICE_MAIN_DOCUMENT,
            ],
            'AdviceAttachment' => [
                'entity' => new AdviceAttachment(
                    new Advice(),
                    new \DateTimeImmutable(),
                    AttachmentType::ADVICE,
                    AttachmentLanguage::DUTCH,
                ),
                'expectedType' => ElasticDocumentType::ATTACHMENT,
            ],
        ];
    }

    #[DataProvider('fromEntityProvider')]
    public function testFromEntity(object $entity, ElasticDocumentType $expectedType): void
    {
        self::assertEquals(
            $expectedType,
            ElasticDocumentType::fromEntity($entity),
        );
    }

    /**
     * @return array<string, array{entityClass: string, expectedType: ElasticDocumentType}>
     */
    public static function fromEntityClassProvider(): array
    {
        return [
            'OtherPublication' => [
                'entityClass' => OtherPublication::class,
                'expectedType' => ElasticDocumentType::OTHER_PUBLICATION,
            ],
            'OtherPublicationMainDocument' => [
                'entityClass' => OtherPublicationMainDocument::class,
                'expectedType' => ElasticDocumentType::OTHER_PUBLICATION_MAIN_DOCUMENT,
            ],
            'OtherPublicationAttachment' => [
                'entityClass' => OtherPublicationAttachment::class,
                'expectedType' => ElasticDocumentType::ATTACHMENT,
            ],
            'AnnualReport' => [
                'entityClass' => AnnualReport::class,
                'expectedType' => ElasticDocumentType::ANNUAL_REPORT,
            ],
            'AnnualReportMainDocument' => [
                'entityClass' => AnnualReportMainDocument::class,
                'expectedType' => ElasticDocumentType::ANNUAL_REPORT_MAIN_DOCUMENT,
            ],
            'AnnualReportAttachment' => [
                'entityClass' => AnnualReportAttachment::class,
                'expectedType' => ElasticDocumentType::ATTACHMENT,
            ],
            'InvestigationReport' => [
                'entityClass' => InvestigationReport::class,
                'expectedType' => ElasticDocumentType::INVESTIGATION_REPORT,
            ],
            'InvestigationReportMainDocument' => [
                'entityClass' => InvestigationReportMainDocument::class,
                'expectedType' => ElasticDocumentType::INVESTIGATION_REPORT_MAIN_DOCUMENT,
            ],
            'InvestigationReportAttachment' => [
                'entityClass' => InvestigationReportAttachment::class,
                'expectedType' => ElasticDocumentType::ATTACHMENT,
            ],
            'Disposition' => [
                'entityClass' => Disposition::class,
                'expectedType' => ElasticDocumentType::DISPOSITION,
            ],
            'DispositionMainDocument' => [
                'entityClass' => DispositionMainDocument::class,
                'expectedType' => ElasticDocumentType::DISPOSITION_MAIN_DOCUMENT,
            ],
            'DispositionAttachment' => [
                'entityClass' => DispositionAttachment::class,
                'expectedType' => ElasticDocumentType::ATTACHMENT,
            ],
            'Advice' => [
                'entityClass' => Advice::class,
                'expectedType' => ElasticDocumentType::ADVICE,
            ],
            'AdviceMainDocument' => [
                'entityClass' => AdviceMainDocument::class,
                'expectedType' => ElasticDocumentType::ADVICE_MAIN_DOCUMENT,
            ],
            'AdviceAttachment' => [
                'entityClass' => AdviceAttachment::class,
                'expectedType' => ElasticDocumentType::ATTACHMENT,
            ],
        ];
    }

    #[DataProvider('fromEntityClassProvider')]
    public function testFromEntityClass(string $entityClass, ElasticDocumentType $expectedType): void
    {
        self::assertEquals(
            $expectedType,
            ElasticDocumentType::fromEntityClass($entityClass),
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
        $this->assertMatchesSnapshot(
            ElasticDocumentType::getMainTypeValues(),
        );
    }

    public function testGetSubTypeValues(): void
    {
        $this->assertMatchesSnapshot(
            ElasticDocumentType::getSubTypeValues(),
        );
    }

    public function testGetMainDocumentTypeValues(): void
    {
        $this->assertMatchesSnapshot(
            ElasticDocumentType::getMainDocumentTypeValues(),
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

    #[DataProvider('fromDossierTypeProvider')]
    public function testFromDossierType(DossierType $input, ElasticDocumentType $expectedType): void
    {
        self::assertEquals(
            $expectedType,
            ElasticDocumentType::fromDossierType($input),
        );
    }

    /**
     * @return array<string, array{input:DossierType, expectedType:ElasticDocumentType}>
     */
    public static function fromDossierTypeProvider(): array
    {
        return [
            'woo-decision' => [
                'input' => DossierType::WOO_DECISION,
                'expectedType' => ElasticDocumentType::WOO_DECISION,
            ],
            'advice' => [
                'input' => DossierType::ADVICE,
                'expectedType' => ElasticDocumentType::ADVICE,
            ],
            'annual-report' => [
                'input' => DossierType::ANNUAL_REPORT,
                'expectedType' => ElasticDocumentType::ANNUAL_REPORT,
            ],
            'complaint-judgment' => [
                'input' => DossierType::COMPLAINT_JUDGEMENT,
                'expectedType' => ElasticDocumentType::COMPLAINT_JUDGEMENT,
            ],
            'disposition' => [
                'input' => DossierType::DISPOSITION,
                'expectedType' => ElasticDocumentType::DISPOSITION,
            ],
            'covenant' => [
                'input' => DossierType::COVENANT,
                'expectedType' => ElasticDocumentType::COVENANT,
            ],
            'other-publication' => [
                'input' => DossierType::OTHER_PUBLICATION,
                'expectedType' => ElasticDocumentType::OTHER_PUBLICATION,
            ],
            'investigation-report' => [
                'input' => DossierType::INVESTIGATION_REPORT,
                'expectedType' => ElasticDocumentType::INVESTIGATION_REPORT,
            ],
        ];
    }
}
