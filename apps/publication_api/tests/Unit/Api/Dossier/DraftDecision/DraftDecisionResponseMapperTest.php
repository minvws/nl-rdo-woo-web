<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\DraftDecision;

use Mockery;
use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionMainDocumentResponseDto;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionResponseMapper;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\Url;
use Symfony\Component\Uid\Uuid;

final class DraftDecisionResponseMapperTest extends UnitTestCase
{
    public function testFromEntity(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $externalId = $this->getFaker()->externalId();
        $summary = $this->getFaker()->sentence();
        $title = DossierTitle::create($this->getFaker()->sentence());
        $dossierDate = $this->getFaker()->plainDate();
        $publicationDate = $this->getFaker()->plainDate();
        $mainDocumentType = $this->getFaker()->attachmentType(DraftDecisionMainDocument::getAllowedTypes());

        $department = Mockery::mock(Department::class);
        $department->expects('getId')->andReturn(Uuid::v6());
        $department->expects('getName')->andReturn($this->getFaker()->company());

        $organisation = new Organisation();
        $organisation->setName($this->getFaker()->company());

        $draftDecision = new DraftDecision();
        $draftDecision->setOrganisation($organisation);
        $draftDecision->addDepartment($department);
        $draftDecision->setStatus(DossierStatus::CONCEPT);
        $draftDecision->setMainDocument(
            new DraftDecisionMainDocument(
                $draftDecision,
                $this->getFaker()->plainDate(),
                $mainDocumentType,
                $this->getFaker()->attachmentLanguage(),
            ),
        );
        $draftDecision->setExternalId($externalId);
        $draftDecision->setDossierNumber($dossierNumber);
        $draftDecision->setTitle($title);
        $draftDecision->setSummary($summary);
        $draftDecision->setDateFrom($dossierDate);
        $draftDecision->setPublicationDate($publicationDate);

        $mainDocumentResponseDto = new DraftDecisionMainDocumentResponseDto(
            Uuid::v6(),
            $mainDocumentType,
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->plainDate(),
            $this->getFaker()->fileName()->toString(),
            UploadStatus::UPLOAD_REQUIRED,
            new LinkCollection(),
        );

        $mainDocumentResponseDtoFactory = Mockery::mock(MainDocumentResponseDtoFactory::class);
        $mainDocumentResponseDtoFactory->expects('fromEntityWithoutGrounds')->andReturn($mainDocumentResponseDto);

        $attachmentResponseDtoFactory = Mockery::mock(AttachmentResponseDtoFactory::class);
        $attachmentResponseDtoFactory->expects('fromDossier')->andReturn([]);

        $apiUrlGenerator = Mockery::mock(ApiUrlGenerator::class);
        $apiUrlGenerator->expects('buildUrlFromRoute')->andReturn(Url::create($this->getFaker()->url()));

        $draftDecisionResponseMapper = new DraftDecisionResponseMapper(
            $apiUrlGenerator,
            $attachmentResponseDtoFactory,
            Mockery::mock(DossierPathHelper::class),
            $mainDocumentResponseDtoFactory,
        );

        $draftDecisionResponseDto = $draftDecisionResponseMapper->fromEntity($draftDecision);

        self::assertSame($draftDecision->getId(), $draftDecisionResponseDto->id);
        self::assertSame($externalId, $draftDecisionResponseDto->externalId);
        self::assertSame($dossierNumber, $draftDecisionResponseDto->dossierNumber);
        self::assertSame($title, $draftDecisionResponseDto->title);
        self::assertSame($summary, $draftDecisionResponseDto->summary);
        self::assertSame($publicationDate, $draftDecisionResponseDto->publicationDate);
        self::assertSame(DossierStatus::CONCEPT, $draftDecisionResponseDto->status);
        self::assertSame($mainDocumentResponseDto, $draftDecisionResponseDto->mainDocument);
        self::assertSame([], $draftDecisionResponseDto->attachments);
        self::assertSame($dossierDate, $draftDecisionResponseDto->dossierDate);
    }
}
