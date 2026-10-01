<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\OtherPublication;

use Mockery;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationMainDocumentRequestDto;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationRequestDto;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationRequestMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

final class OtherPublicationRequestMapperTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $dossierDate = $this->getFaker()->plainDate();
        $publicationDate = $this->getFaker()->plainDate();
        $dossierNumber = $this->getFaker()->dossierNumber();
        $summary = $this->getFaker()->sentence();
        $title = DossierTitle::create($this->getFaker()->sentence());

        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $otherPublicationRequestDto = new OtherPublicationRequestDto(
            Uuid::v6(),
            null,
            $summary,
            $title,
            [],
            $dossierDate,
            $dossierNumber,
            $publicationDate,
            new OtherPublicationMainDocumentRequestDto(
                $this->getFaker()->fileName(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentLanguage(),
                $this->getFaker()->attachmentType(OtherPublicationMainDocument::getAllowedTypes()),
            ),
        );

        $otherPublicationRequestMapper = new OtherPublicationRequestMapper();

        $otherPublication = $otherPublicationRequestMapper->create(
            $otherPublicationRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        self::assertSame($dossierExternalId, $otherPublication->getExternalId());
        self::assertSame(DossierStatus::CONCEPT, $otherPublication->getStatus());
        self::assertSame($documentPrefix, $otherPublication->getDocumentPrefix());
        self::assertSame($dossierDate, $otherPublication->getDateFrom());
        self::assertSame($publicationDate, $otherPublication->getPublicationDate());
        self::assertSame($dossierNumber, $otherPublication->getDossierNumber());
        self::assertSame($summary, $otherPublication->getSummary());
        self::assertSame($title, $otherPublication->getTitle());
        self::assertSame($organisation, $otherPublication->getOrganisation());
        self::assertSame($subject, $otherPublication->getSubject());
        self::assertSame([$department], $otherPublication->getDepartments()->getValues());
    }
}
