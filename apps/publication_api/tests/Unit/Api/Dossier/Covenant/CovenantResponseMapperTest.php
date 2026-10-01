<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Covenant;

use Mockery;
use PublicationApi\Api\Attachment\AttachmentResponseDtoFactory;
use PublicationApi\Api\Dossier\Covenant\CovenantMainDocumentResponseDto;
use PublicationApi\Api\Dossier\Covenant\CovenantResponseMapper;
use PublicationApi\Api\MainDocument\MainDocumentResponseDtoFactory;
use PublicationApi\Api\NoticeNotPublic\NoticeNotPublicResponseDtoFactory;
use PublicationApi\Domain\OpenApi\Links\ApiUrlGenerator;
use PublicationApi\Domain\OpenApi\Links\LinkCollection;
use PublicationApi\Domain\Upload\UploadStatus;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Domain\Publication\Dossier\ViewModel\DossierPathHelper;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Shared\ValueObject\Url;
use Symfony\Component\Uid\Uuid;

final class CovenantResponseMapperTest extends UnitTestCase
{
    public function testFromEntity(): void
    {
        $dossierNumber = $this->getFaker()->dossierNumber();
        $externalId = $this->getFaker()->externalId();
        $summary = $this->getFaker()->sentence();
        $title = DossierTitle::create($this->getFaker()->sentence());
        $dateFrom = $this->getFaker()->plainDate();
        $dateTo = $this->getFaker()->plainDate();
        $publicationDate = $this->getFaker()->plainDate();
        $parties = [$this->getFaker()->company(), $this->getFaker()->company()];
        $previousVersionLink = $this->getFaker()->url();

        $department = Mockery::mock(Department::class);
        $department->expects('getId')->andReturn(Uuid::v6());
        $department->expects('getName')->andReturn($this->getFaker()->company());

        $organisation = new Organisation();
        $organisation->setName($this->getFaker()->company());

        $covenant = new Covenant();
        $covenant->setOrganisation($organisation);
        $covenant->addDepartment($department);
        $covenant->setStatus(DossierStatus::CONCEPT);
        $covenant->setMainDocument(
            new CovenantMainDocument($covenant, $this->getFaker()->plainDate(), $this->getFaker()->attachmentLanguage()),
        );
        $covenant->setExternalId($externalId);
        $covenant->setDossierNumber($dossierNumber);
        $covenant->setTitle($title);
        $covenant->setSummary($summary);
        $covenant->setDateFrom($dateFrom);
        $covenant->setDateTo($dateTo);
        $covenant->setPublicationDate($publicationDate);
        $covenant->setParties($parties);
        $covenant->setPreviousVersionLink($previousVersionLink);

        $mainDocumentResponseDto = new CovenantMainDocumentResponseDto(
            Uuid::v6(),
            AttachmentType::COVENANT,
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

        $covenantResponseMapper = new CovenantResponseMapper(
            $apiUrlGenerator,
            $attachmentResponseDtoFactory,
            Mockery::mock(DossierPathHelper::class),
            $mainDocumentResponseDtoFactory,
            Mockery::mock(NoticeNotPublicResponseDtoFactory::class),
        );

        $covenantResponseDto = $covenantResponseMapper->fromEntity($covenant);

        self::assertSame($covenant->getId(), $covenantResponseDto->id);
        self::assertSame($externalId, $covenantResponseDto->externalId);
        self::assertSame($dossierNumber, $covenantResponseDto->dossierNumber);
        self::assertSame($title, $covenantResponseDto->title);
        self::assertSame($summary, $covenantResponseDto->summary);
        self::assertSame($publicationDate, $covenantResponseDto->publicationDate);
        self::assertSame(DossierStatus::CONCEPT, $covenantResponseDto->status);
        self::assertSame($mainDocumentResponseDto, $covenantResponseDto->mainDocument);
        self::assertNull($covenantResponseDto->noticeNotPublic);
        self::assertSame($attachmentResponseDtos, $covenantResponseDto->attachments);
        self::assertSame($dateFrom, $covenantResponseDto->dateFrom);
        self::assertSame($dateTo, $covenantResponseDto->dateTo);
        self::assertSame($previousVersionLink, $covenantResponseDto->previousVersionLink);
        self::assertSame($parties, $covenantResponseDto->parties);
    }
}
