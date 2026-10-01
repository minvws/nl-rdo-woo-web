<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\RequestForAdvice;

use Mockery;
use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceMainDocumentResponseDto;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceResponseMapper;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicResponseDtoFactory;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\Url;
use Symfony\Component\Uid\Uuid;

final class RequestForAdviceResponseMapperTest extends UnitTestCase
{
    public function testFromEntity(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $externalId = $this->getFaker()->externalId();
        $summary = $this->getFaker()->sentence();
        $title = DossierTitle::create($this->getFaker()->sentence());
        $dossierDate = $this->getFaker()->plainDate();
        $publicationDate = $this->getFaker()->plainDate();
        $mainDocumentType = $this->getFaker()->attachmentType(RequestForAdviceMainDocument::getAllowedTypes());
        $link = $this->getFaker()->url();
        $advisoryBodies = [$this->getFaker()->company()];

        $department = Mockery::mock(Department::class);
        $department->expects('getId')->andReturn(Uuid::v6());
        $department->expects('getName')->andReturn($this->getFaker()->company());

        $organisation = new Organisation();
        $organisation->setName($this->getFaker()->company());

        $requestForAdvice = new RequestForAdvice();
        $requestForAdvice->setOrganisation($organisation);
        $requestForAdvice->addDepartment($department);
        $requestForAdvice->setStatus(DossierStatus::CONCEPT);
        $requestForAdvice->setMainDocument(
            new RequestForAdviceMainDocument(
                $requestForAdvice,
                $this->getFaker()->plainDate(),
                $mainDocumentType,
                $this->getFaker()->attachmentLanguage(),
            ),
        );
        $requestForAdvice->setExternalId($externalId);
        $requestForAdvice->setDossierNumber($dossierNumber);
        $requestForAdvice->setTitle($title);
        $requestForAdvice->setSummary($summary);
        $requestForAdvice->setDateFrom($dossierDate);
        $requestForAdvice->setPublicationDate($publicationDate);
        $requestForAdvice->setLink($link);
        $requestForAdvice->setAdvisoryBodies($advisoryBodies);

        $mainDocumentResponseDto = new RequestForAdviceMainDocumentResponseDto(
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

        $requestForAdviceResponseMapper = new RequestForAdviceResponseMapper(
            $apiUrlGenerator,
            $attachmentResponseDtoFactory,
            Mockery::mock(DossierPathHelper::class),
            $mainDocumentResponseDtoFactory,
            Mockery::mock(NoticeNotPublicResponseDtoFactory::class),
        );

        $requestForAdviceResponseDto = $requestForAdviceResponseMapper->fromEntity($requestForAdvice);

        self::assertSame($requestForAdvice->getId(), $requestForAdviceResponseDto->id);
        self::assertSame($externalId, $requestForAdviceResponseDto->externalId);
        self::assertSame($dossierNumber, $requestForAdviceResponseDto->dossierNumber);
        self::assertSame($title, $requestForAdviceResponseDto->title);
        self::assertSame($summary, $requestForAdviceResponseDto->summary);
        self::assertSame($publicationDate, $requestForAdviceResponseDto->publicationDate);
        self::assertSame(DossierStatus::CONCEPT, $requestForAdviceResponseDto->status);
        self::assertSame($mainDocumentResponseDto, $requestForAdviceResponseDto->mainDocument);
        self::assertNull($requestForAdviceResponseDto->noticeNotPublic);
        self::assertSame($attachmentResponseDtos, $requestForAdviceResponseDto->attachments);
        self::assertSame($dossierDate, $requestForAdviceResponseDto->dossierDate);
        self::assertSame($link, $requestForAdviceResponseDto->link);
        self::assertSame($advisoryBodies, $requestForAdviceResponseDto->advisoryBodies);
    }
}
