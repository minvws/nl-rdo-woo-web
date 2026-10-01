<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Advice;

use Mockery;
use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Dossier\Advice\AdviceMainDocumentResponseDto;
use PublicationApi\Api\Dossier\Advice\AdviceResponseMapper;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicResponseDtoFactory;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceMainDocument;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\Url;
use Symfony\Component\Uid\Uuid;

final class AdviceResponseMapperTest extends UnitTestCase
{
    public function testFromEntity(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $externalId = $this->getFaker()->externalId();
        $summary = $this->getFaker()->sentence();
        $title = DossierTitle::create($this->getFaker()->sentence());
        $dossierDate = $this->getFaker()->plainDate();
        $publicationDate = $this->getFaker()->plainDate();
        $mainDocumentType = $this->getFaker()->attachmentType(AdviceMainDocument::getAllowedTypes());

        $department = Mockery::mock(Department::class);
        $department->expects('getId')->andReturn(Uuid::v6());
        $department->expects('getName')->andReturn($this->getFaker()->company());

        $organisation = new Organisation();
        $organisation->setName($this->getFaker()->company());

        $advice = new Advice();
        $advice->setOrganisation($organisation);
        $advice->addDepartment($department);
        $advice->setStatus(DossierStatus::CONCEPT);
        $advice->setMainDocument(
            new AdviceMainDocument(
                $advice,
                $this->getFaker()->plainDate(),
                $mainDocumentType,
                $this->getFaker()->attachmentLanguage(),
            ),
        );
        $advice->setExternalId($externalId);
        $advice->setDossierNumber($dossierNumber);
        $advice->setTitle($title);
        $advice->setSummary($summary);
        $advice->setDateFrom($dossierDate);
        $advice->setPublicationDate($publicationDate);

        $mainDocumentResponseDto = new AdviceMainDocumentResponseDto(
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

        $adviceResponseMapper = new AdviceResponseMapper(
            $apiUrlGenerator,
            $attachmentResponseDtoFactory,
            Mockery::mock(DossierPathHelper::class),
            $mainDocumentResponseDtoFactory,
            Mockery::mock(NoticeNotPublicResponseDtoFactory::class),
        );

        $adviceResponseDto = $adviceResponseMapper->fromEntity($advice);

        self::assertSame($advice->getId(), $adviceResponseDto->id);
        self::assertSame($externalId, $adviceResponseDto->externalId);
        self::assertSame($dossierNumber, $adviceResponseDto->dossierNumber);
        self::assertSame($title, $adviceResponseDto->title);
        self::assertSame($summary, $adviceResponseDto->summary);
        self::assertSame($publicationDate, $adviceResponseDto->publicationDate);
        self::assertSame(DossierStatus::CONCEPT, $adviceResponseDto->status);
        self::assertSame($mainDocumentResponseDto, $adviceResponseDto->mainDocument);
        self::assertNull($adviceResponseDto->noticeNotPublic);
        self::assertSame($attachmentResponseDtos, $adviceResponseDto->attachments);
        self::assertSame($dossierDate, $adviceResponseDto->dossierDate);
    }
}
