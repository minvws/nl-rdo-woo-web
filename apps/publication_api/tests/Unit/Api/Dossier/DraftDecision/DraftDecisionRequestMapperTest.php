<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\DraftDecision;

use Mockery;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionMainDocumentRequestDto;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionRequestDto;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionRequestMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

final class DraftDecisionRequestMapperTest extends UnitTestCase
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

        $draftDecisionRequestDto = new DraftDecisionRequestDto(
            Uuid::v6(),
            null,
            $summary,
            $title,
            [],
            $dossierDate,
            $dossierNumber,
            $publicationDate,
            new DraftDecisionMainDocumentRequestDto(
                $this->getFaker()->fileName(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentLanguage(),
                $this->getFaker()->attachmentType(DraftDecisionMainDocument::getAllowedTypes()),
            ),
        );

        $draftDecisionRequestMapper = new DraftDecisionRequestMapper();

        $draftDecision = $draftDecisionRequestMapper->create(
            $draftDecisionRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        self::assertSame($dossierExternalId, $draftDecision->getExternalId());
        self::assertSame(DossierStatus::CONCEPT, $draftDecision->getStatus());
        self::assertSame($documentPrefix, $draftDecision->getDocumentPrefix());
        self::assertSame($dossierDate, $draftDecision->getDateFrom());
        self::assertSame($publicationDate, $draftDecision->getPublicationDate());
        self::assertSame($dossierNumber, $draftDecision->getDossierNumber());
        self::assertSame($summary, $draftDecision->getSummary());
        self::assertSame($title, $draftDecision->getTitle());
        self::assertSame($organisation, $draftDecision->getOrganisation());
        self::assertSame($subject, $draftDecision->getSubject());
        self::assertSame([$department], $draftDecision->getDepartments()->getValues());
    }
}
