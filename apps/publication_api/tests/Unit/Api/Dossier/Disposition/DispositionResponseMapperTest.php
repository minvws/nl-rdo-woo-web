<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Disposition;

use Mockery;
use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Dossier\Disposition\DispositionMainDocumentResponseDto;
use PublicationApi\Api\Dossier\Disposition\DispositionResponseMapper;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicResponseDtoFactory;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\Url;
use Symfony\Component\Uid\Uuid;

final class DispositionResponseMapperTest extends UnitTestCase
{
    public function testFromEntity(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $externalId = $this->getFaker()->externalId();
        $summary = $this->getFaker()->sentence();
        $title = DossierTitle::create($this->getFaker()->sentence());
        $dossierDate = $this->getFaker()->plainDate();
        $publicationDate = $this->getFaker()->plainDate();
        $mainDocumentType = $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes());

        $department = Mockery::mock(Department::class);
        $department->expects('getId')->andReturn(Uuid::v6());
        $department->expects('getName')->andReturn($this->getFaker()->company());

        $organisation = new Organisation();
        $organisation->setName($this->getFaker()->company());

        $disposition = new Disposition();
        $disposition->setOrganisation($organisation);
        $disposition->addDepartment($department);
        $disposition->setStatus(DossierStatus::CONCEPT);
        $disposition->setMainDocument(
            new DispositionMainDocument(
                $disposition,
                $this->getFaker()->plainDate(),
                $mainDocumentType,
                $this->getFaker()->attachmentLanguage(),
            ),
        );
        $disposition->setExternalId($externalId);
        $disposition->setDossierNumber($dossierNumber);
        $disposition->setTitle($title);
        $disposition->setSummary($summary);
        $disposition->setDateFrom($dossierDate);
        $disposition->setPublicationDate($publicationDate);

        $mainDocumentResponseDto = new DispositionMainDocumentResponseDto(
            Uuid::v6(),
            $mainDocumentType,
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->plainDate(),
            [],
            $this->getFaker()->fileName()->toString(),
            UploadStatus::UPLOAD_REQUIRED,
            new LinkCollection(),
        );
        $attachmentResponseDtos = [];

        $mainDocumentResponseDtoFactory = Mockery::mock(MainDocumentResponseDtoFactory::class);
        $mainDocumentResponseDtoFactory->expects('fromEntity')->andReturn($mainDocumentResponseDto);

        $attachmentResponseDtoFactory = Mockery::mock(AttachmentResponseDtoFactory::class);
        $attachmentResponseDtoFactory->expects('fromDossier')->andReturn($attachmentResponseDtos);

        $apiUrlGenerator = Mockery::mock(ApiUrlGenerator::class);
        $apiUrlGenerator->expects('buildUrlFromRoute')->andReturn(Url::create($this->getFaker()->url()));

        $dispositionResponseMapper = new DispositionResponseMapper(
            $apiUrlGenerator,
            $attachmentResponseDtoFactory,
            Mockery::mock(DossierPathHelper::class),
            $mainDocumentResponseDtoFactory,
            Mockery::mock(NoticeNotPublicResponseDtoFactory::class),
        );

        $dispositionResponseDto = $dispositionResponseMapper->fromEntity($disposition);

        self::assertSame($disposition->getId(), $dispositionResponseDto->id);
        self::assertSame($externalId, $dispositionResponseDto->externalId);
        self::assertSame($dossierNumber, $dispositionResponseDto->dossierNumber);
        self::assertSame($title, $dispositionResponseDto->title);
        self::assertSame($summary, $dispositionResponseDto->summary);
        self::assertSame($publicationDate, $dispositionResponseDto->publicationDate);
        self::assertSame(DossierStatus::CONCEPT, $dispositionResponseDto->status);
        self::assertSame($mainDocumentResponseDto, $dispositionResponseDto->mainDocument);
        self::assertNull($dispositionResponseDto->noticeNotPublic);
        self::assertSame($attachmentResponseDtos, $dispositionResponseDto->attachments);
        self::assertSame($dossierDate, $dispositionResponseDto->dossierDate);
    }
}
