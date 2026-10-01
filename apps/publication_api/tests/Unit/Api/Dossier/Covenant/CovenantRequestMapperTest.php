<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Covenant;

use Mockery;
use PublicationApi\Api\Dossier\Covenant\CovenantMainDocumentRequestDto;
use PublicationApi\Api\Dossier\Covenant\CovenantRequestDto;
use PublicationApi\Api\Dossier\Covenant\CovenantRequestMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

final class CovenantRequestMapperTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $dateFrom = $this->getFaker()->plainDate();
        $dateTo = $this->getFaker()->plainDate();
        $publicationDate = $this->getFaker()->plainDate();
        $dossierNumber = $this->getFaker()->dossierNumber();
        $summary = $this->getFaker()->sentence();
        $title = DossierTitle::create($this->getFaker()->sentence());
        $parties = [$this->getFaker()->company(), $this->getFaker()->company()];
        $previousVersionLink = $this->getFaker()->url();

        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $covenantRequestDto = new CovenantRequestDto(
            Uuid::v6(),
            null,
            $summary,
            $title,
            [],
            $dateFrom,
            $dateTo,
            $dossierNumber,
            $publicationDate,
            $parties,
            $previousVersionLink,
            new CovenantMainDocumentRequestDto(
                $this->getFaker()->fileName(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $covenantRequestMapper = new CovenantRequestMapper();

        $covenant = $covenantRequestMapper->create(
            $covenantRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        self::assertSame($dossierExternalId, $covenant->getExternalId());
        self::assertSame(DossierStatus::CONCEPT, $covenant->getStatus());
        self::assertSame($documentPrefix, $covenant->getDocumentPrefix());
        self::assertSame($dateFrom, $covenant->getDateFrom());
        self::assertSame($dateTo, $covenant->getDateTo());
        self::assertSame($publicationDate, $covenant->getPublicationDate());
        self::assertSame($dossierNumber, $covenant->getDossierNumber());
        self::assertSame($summary, $covenant->getSummary());
        self::assertSame($title, $covenant->getTitle());
        self::assertSame($parties, $covenant->getParties());
        self::assertSame($previousVersionLink, $covenant->getPreviousVersionLink());
        self::assertSame($organisation, $covenant->getOrganisation());
        self::assertSame($subject, $covenant->getSubject());
        self::assertSame([$department], $covenant->getDepartments()->getValues());
    }
}
