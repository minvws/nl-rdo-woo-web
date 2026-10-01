<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Disposition;

use Mockery;
use PublicationApi\Api\Dossier\Disposition\DispositionMainDocumentRequestDto;
use PublicationApi\Api\Dossier\Disposition\DispositionRequestDto;
use PublicationApi\Api\Dossier\Disposition\DispositionRequestMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

final class DispositionRequestMapperTest extends UnitTestCase
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

        $dispositionRequestDto = new DispositionRequestDto(
            Uuid::v6(),
            null,
            $summary,
            $title,
            [],
            $dossierDate,
            $dossierNumber,
            $publicationDate,
            new DispositionMainDocumentRequestDto(
                $this->getFaker()->fileName(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentLanguage(),
                $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes()),
            ),
        );

        $dispositionRequestMapper = new DispositionRequestMapper();

        $disposition = $dispositionRequestMapper->create(
            $dispositionRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        self::assertSame($dossierExternalId, $disposition->getExternalId());
        self::assertSame(DossierStatus::CONCEPT, $disposition->getStatus());
        self::assertSame($documentPrefix, $disposition->getDocumentPrefix());
        self::assertSame($dossierDate, $disposition->getDateFrom());
        self::assertSame($publicationDate, $disposition->getPublicationDate());
        self::assertSame($dossierNumber, $disposition->getDossierNumber());
        self::assertSame($summary, $disposition->getSummary());
        self::assertSame($title, $disposition->getTitle());
        self::assertSame($organisation, $disposition->getOrganisation());
        self::assertSame($subject, $disposition->getSubject());
        self::assertSame([$department], $disposition->getDepartments()->getValues());
    }
}
