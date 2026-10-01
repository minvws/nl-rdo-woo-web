<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\WooDecision;

use Mockery;
use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Dossier\WooDecision\Document\WooDecisionDocumentResponseDtoFactory;
use PublicationApi\Api\Dossier\WooDecision\Inquiry\InquiryLinkFactory;
use PublicationApi\Api\Dossier\WooDecision\WooDecisionMainDocumentResponseDto;
use PublicationApi\Api\Dossier\WooDecision\WooDecisionResponseMapper;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\Url;
use Symfony\Component\Uid\Uuid;

final class WooDecisionResponseMapperTest extends UnitTestCase
{
    public function testFromEntity(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $externalId = $this->getFaker()->externalId();
        $summary = $this->getFaker()->sentence();
        $title = DossierTitle::create($this->getFaker()->sentence());
        $dateFrom = $this->getFaker()->plainDate();
        $previewDate = $this->getFaker()->plainDate();
        $publicationDate = $this->getFaker()->plainDate();

        $department = Mockery::mock(Department::class);
        $department->expects('getId')->andReturn(Uuid::v6());
        $department->expects('getName')->andReturn($this->getFaker()->company());

        $organisation = new Organisation();
        $organisation->setName($this->getFaker()->company());

        $wooDecision = new WooDecision();
        $wooDecision->setOrganisation($organisation);
        $wooDecision->addDepartment($department);
        $wooDecision->setStatus(DossierStatus::CONCEPT);
        $wooDecision->setMainDocument(
            new WooDecisionMainDocument($wooDecision, $this->getFaker()->plainDate(), $this->getFaker()->attachmentLanguage()),
        );
        $wooDecision->setExternalId($externalId);
        $wooDecision->setDossierNumber($dossierNumber);
        $wooDecision->setTitle($title);
        $wooDecision->setSummary($summary);
        $wooDecision->setDateFrom($dateFrom);
        $wooDecision->setPreviewDate($previewDate);
        $wooDecision->setPublicationDate($publicationDate);
        $wooDecision->setDecision(DecisionType::PUBLIC);
        $wooDecision->setPublicationReason(PublicationReason::WOO_REQUEST);

        $mainDocumentResponseDto = new WooDecisionMainDocumentResponseDto(
            Uuid::v6(),
            AttachmentType::JUDGEMENT_ON_WOB_WOO_REQUEST,
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->plainDate(),
            [],
            $this->getFaker()->fileName()->toString(),
            UploadStatus::UPLOAD_REQUIRED,
            new LinkCollection(),
        );
        $attachmentResponseDtos = [];
        $documentResponseDtos = [];

        $mainDocumentResponseDtoFactory = Mockery::mock(MainDocumentResponseDtoFactory::class);
        $mainDocumentResponseDtoFactory->expects('fromEntity')->andReturn($mainDocumentResponseDto);

        $attachmentResponseDtoFactory = Mockery::mock(AttachmentResponseDtoFactory::class);
        $attachmentResponseDtoFactory->expects('fromDossier')->andReturn($attachmentResponseDtos);

        $wooDecisionDocumentResponseDtoFactory = Mockery::mock(WooDecisionDocumentResponseDtoFactory::class);
        $wooDecisionDocumentResponseDtoFactory->expects('fromWooDecision')->with($wooDecision)->andReturn($documentResponseDtos);

        $apiUrlGenerator = Mockery::mock(ApiUrlGenerator::class);
        $apiUrlGenerator->expects('buildUrlFromRoute')->andReturn(Url::create($this->getFaker()->url()));

        $wooDecisionResponseMapper = new WooDecisionResponseMapper(
            $apiUrlGenerator,
            $attachmentResponseDtoFactory,
            Mockery::mock(DossierPathHelper::class),
            Mockery::mock(InquiryLinkFactory::class),
            $mainDocumentResponseDtoFactory,
            $wooDecisionDocumentResponseDtoFactory,
        );

        $wooDecisionResponseDto = $wooDecisionResponseMapper->fromEntity($wooDecision);

        self::assertSame($wooDecision->getId(), $wooDecisionResponseDto->id);
        self::assertSame($externalId, $wooDecisionResponseDto->externalId);
        self::assertSame($dossierNumber, $wooDecisionResponseDto->dossierNumber);
        self::assertSame($title, $wooDecisionResponseDto->title);
        self::assertSame($summary, $wooDecisionResponseDto->summary);
        self::assertSame($publicationDate, $wooDecisionResponseDto->publicationDate);
        self::assertSame(DossierStatus::CONCEPT, $wooDecisionResponseDto->status);
        self::assertSame($mainDocumentResponseDto, $wooDecisionResponseDto->mainDocument);
        self::assertSame($attachmentResponseDtos, $wooDecisionResponseDto->attachments);
        self::assertSame($dateFrom, $wooDecisionResponseDto->dateFrom);
        self::assertSame(DecisionType::PUBLIC, $wooDecisionResponseDto->decision);
        self::assertSame(PublicationReason::WOO_REQUEST, $wooDecisionResponseDto->reason);
        self::assertSame($previewDate, $wooDecisionResponseDto->previewDate);
        self::assertSame($documentResponseDtos, $wooDecisionResponseDto->documents);
    }
}
