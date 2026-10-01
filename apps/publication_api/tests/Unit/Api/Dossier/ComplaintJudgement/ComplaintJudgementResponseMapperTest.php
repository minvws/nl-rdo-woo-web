<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\ComplaintJudgement;

use Mockery;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementMainDocumentResponseDto;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementResponseMapper;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicResponseDtoFactory;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\Url;
use Symfony\Component\Uid\Uuid;

final class ComplaintJudgementResponseMapperTest extends UnitTestCase
{
    public function testFromEntity(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $externalId = $this->getFaker()->externalId();
        $summary = $this->getFaker()->sentence();
        $title = DossierTitle::create($this->getFaker()->sentence());
        $dossierDate = $this->getFaker()->plainDate();
        $publicationDate = $this->getFaker()->plainDate();
        $mainDocumentType = $this->getFaker()->attachmentType(ComplaintJudgementMainDocument::getAllowedTypes());

        $department = Mockery::mock(Department::class);
        $department->expects('getId')->andReturn(Uuid::v6());
        $department->expects('getName')->andReturn($this->getFaker()->company());

        $organisation = new Organisation();
        $organisation->setName($this->getFaker()->company());

        $complaintJudgement = new ComplaintJudgement();
        $complaintJudgement->setOrganisation($organisation);
        $complaintJudgement->addDepartment($department);
        $complaintJudgement->setStatus(DossierStatus::CONCEPT);
        $complaintJudgement->setMainDocument(
            new ComplaintJudgementMainDocument(
                $complaintJudgement,
                $this->getFaker()->plainDate(),
                $mainDocumentType,
                $this->getFaker()->attachmentLanguage(),
            ),
        );
        $complaintJudgement->setExternalId($externalId);
        $complaintJudgement->setDossierNumber($dossierNumber);
        $complaintJudgement->setTitle($title);
        $complaintJudgement->setSummary($summary);
        $complaintJudgement->setDateFrom($dossierDate);
        $complaintJudgement->setPublicationDate($publicationDate);

        $mainDocumentResponseDto = new ComplaintJudgementMainDocumentResponseDto(
            Uuid::v6(),
            $mainDocumentType,
            $this->getFaker()->attachmentLanguage(),
            $this->getFaker()->plainDate(),
            [],
            $this->getFaker()->fileName()->toString(),
            UploadStatus::UPLOAD_REQUIRED,
            new LinkCollection(),
        );

        $mainDocumentResponseDtoFactory = Mockery::mock(MainDocumentResponseDtoFactory::class);
        $mainDocumentResponseDtoFactory->expects('fromEntity')->andReturn($mainDocumentResponseDto);

        $apiUrlGenerator = Mockery::mock(ApiUrlGenerator::class);
        $apiUrlGenerator->expects('buildUrlFromRoute')->andReturn(Url::create($this->getFaker()->url()));

        $complaintJudgementResponseMapper = new ComplaintJudgementResponseMapper(
            $apiUrlGenerator,
            Mockery::mock(DossierPathHelper::class),
            $mainDocumentResponseDtoFactory,
            Mockery::mock(NoticeNotPublicResponseDtoFactory::class),
        );

        $complaintJudgementResponseDto = $complaintJudgementResponseMapper->fromEntity($complaintJudgement);

        self::assertSame($complaintJudgement->getId(), $complaintJudgementResponseDto->id);
        self::assertSame($externalId, $complaintJudgementResponseDto->externalId);
        self::assertSame($dossierNumber, $complaintJudgementResponseDto->dossierNumber);
        self::assertSame($title, $complaintJudgementResponseDto->title);
        self::assertSame($summary, $complaintJudgementResponseDto->summary);
        self::assertSame($publicationDate, $complaintJudgementResponseDto->publicationDate);
        self::assertSame(DossierStatus::CONCEPT, $complaintJudgementResponseDto->status);
        self::assertSame($mainDocumentResponseDto, $complaintJudgementResponseDto->mainDocument);
        self::assertNull($complaintJudgementResponseDto->noticeNotPublic);
        self::assertSame($dossierDate, $complaintJudgementResponseDto->dossierDate);
    }
}
