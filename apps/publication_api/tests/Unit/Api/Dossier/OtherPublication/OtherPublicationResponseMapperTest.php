<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\OtherPublication;

use Mockery;
use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationMainDocumentResponseDto;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationResponseMapper;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicResponseDtoFactory;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\Url;
use Symfony\Component\Uid\Uuid;

final class OtherPublicationResponseMapperTest extends UnitTestCase
{
    public function testFromEntity(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $externalId = $this->getFaker()->externalId();
        $summary = $this->getFaker()->sentence();
        $title = DossierTitle::create($this->getFaker()->sentence());
        $dossierDate = $this->getFaker()->plainDate();
        $publicationDate = $this->getFaker()->plainDate();
        $mainDocumentType = $this->getFaker()->attachmentType(OtherPublicationMainDocument::getAllowedTypes());

        $department = Mockery::mock(Department::class);
        $department->expects('getId')->andReturn(Uuid::v6());
        $department->expects('getName')->andReturn($this->getFaker()->company());

        $organisation = new Organisation();
        $organisation->setName($this->getFaker()->company());

        $otherPublication = new OtherPublication();
        $otherPublication->setOrganisation($organisation);
        $otherPublication->addDepartment($department);
        $otherPublication->setStatus(DossierStatus::CONCEPT);
        $otherPublication->setMainDocument(
            new OtherPublicationMainDocument(
                $otherPublication,
                $this->getFaker()->plainDate(),
                $mainDocumentType,
                $this->getFaker()->attachmentLanguage(),
            ),
        );
        $otherPublication->setExternalId($externalId);
        $otherPublication->setDossierNumber($dossierNumber);
        $otherPublication->setTitle($title);
        $otherPublication->setSummary($summary);
        $otherPublication->setDateFrom($dossierDate);
        $otherPublication->setPublicationDate($publicationDate);

        $mainDocumentResponseDto = new OtherPublicationMainDocumentResponseDto(
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

        $otherPublicationResponseMapper = new OtherPublicationResponseMapper(
            $apiUrlGenerator,
            $attachmentResponseDtoFactory,
            Mockery::mock(DossierPathHelper::class),
            $mainDocumentResponseDtoFactory,
            Mockery::mock(NoticeNotPublicResponseDtoFactory::class),
        );

        $otherPublicationResponseDto = $otherPublicationResponseMapper->fromEntity($otherPublication);

        self::assertSame($otherPublication->getId(), $otherPublicationResponseDto->id);
        self::assertSame($externalId, $otherPublicationResponseDto->externalId);
        self::assertSame($dossierNumber, $otherPublicationResponseDto->dossierNumber);
        self::assertSame($title, $otherPublicationResponseDto->title);
        self::assertSame($summary, $otherPublicationResponseDto->summary);
        self::assertSame($publicationDate, $otherPublicationResponseDto->publicationDate);
        self::assertSame(DossierStatus::CONCEPT, $otherPublicationResponseDto->status);
        self::assertSame($mainDocumentResponseDto, $otherPublicationResponseDto->mainDocument);
        self::assertNull($otherPublicationResponseDto->noticeNotPublic);
        self::assertSame($attachmentResponseDtos, $otherPublicationResponseDto->attachments);
        self::assertSame($dossierDate, $otherPublicationResponseDto->dossierDate);
    }
}
