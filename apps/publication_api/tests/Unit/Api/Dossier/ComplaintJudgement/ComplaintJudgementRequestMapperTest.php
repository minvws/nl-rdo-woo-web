<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\ComplaintJudgement;

use Mockery;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementMainDocumentRequestDto;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementRequestDto;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementRequestMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

final class ComplaintJudgementRequestMapperTest extends UnitTestCase
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

        $complaintJudgementRequestDto = new ComplaintJudgementRequestDto(
            Uuid::v6(),
            null,
            $summary,
            $title,
            $dossierDate,
            $dossierNumber,
            $publicationDate,
            new ComplaintJudgementMainDocumentRequestDto(
                $this->getFaker()->fileName(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentLanguage(),
                $this->getFaker()->attachmentType(ComplaintJudgementMainDocument::getAllowedTypes()),
            ),
        );

        $complaintJudgementRequestMapper = new ComplaintJudgementRequestMapper();

        $complaintJudgement = $complaintJudgementRequestMapper->create(
            $complaintJudgementRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        self::assertSame($dossierExternalId, $complaintJudgement->getExternalId());
        self::assertSame(DossierStatus::CONCEPT, $complaintJudgement->getStatus());
        self::assertSame($documentPrefix, $complaintJudgement->getDocumentPrefix());
        self::assertSame($dossierDate, $complaintJudgement->getDateFrom());
        self::assertSame($publicationDate, $complaintJudgement->getPublicationDate());
        self::assertSame($dossierNumber, $complaintJudgement->getDossierNumber());
        self::assertSame($summary, $complaintJudgement->getSummary());
        self::assertSame($title, $complaintJudgement->getTitle());
        self::assertSame($organisation, $complaintJudgement->getOrganisation());
        self::assertSame($subject, $complaintJudgement->getSubject());
        self::assertSame([$department], $complaintJudgement->getDepartments()->getValues());
    }
}
