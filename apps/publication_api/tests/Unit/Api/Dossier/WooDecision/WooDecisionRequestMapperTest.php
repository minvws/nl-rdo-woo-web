<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\WooDecision;

use Mockery;
use PublicationApi\Api\Dossier\WooDecision\WooDecisionMainDocumentRequestDto;
use PublicationApi\Api\Dossier\WooDecision\WooDecisionRequestDto;
use PublicationApi\Api\Dossier\WooDecision\WooDecisionRequestMapper;
use Shared\Domain\Department\Department;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Decision\DecisionType;
use Shared\Domain\Publication\Dossier\Type\WooDecision\PublicationReason;
use Shared\Domain\Publication\Subject\Subject;
use Shared\Tests\Unit\UnitTestCase;
use Shared\ValueObject\DossierTitle;
use Symfony\Component\Uid\Uuid;

final class WooDecisionRequestMapperTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $dossierExternalId = $this->getFaker()->externalId();
        $documentPrefix = $this->getFaker()->documentPrefix();
        $dateFrom = $this->getFaker()->plainDate();
        $dateTo = $this->getFaker()->plainDate();
        $previewDate = $this->getFaker()->plainDate();
        $publicationDate = $this->getFaker()->plainDate();
        $dossierNumber = $this->getFaker()->dossierNumber();
        $summary = $this->getFaker()->sentence();
        $title = DossierTitle::create($this->getFaker()->sentence());

        $organisation = Mockery::mock(Organisation::class);
        $department = Mockery::mock(Department::class);
        $subject = Mockery::mock(Subject::class);

        $wooDecisionRequestDto = new WooDecisionRequestDto(
            Uuid::v6(),
            new WooDecisionMainDocumentRequestDto(
                $this->getFaker()->fileName(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentLanguage(),
            ),
            null,
            $summary,
            $title,
            [],
            $dateFrom,
            $dateTo,
            $dossierNumber,
            $publicationDate,
            DecisionType::PUBLIC,
            PublicationReason::WOO_REQUEST,
            $previewDate,
        );

        $wooDecisionRequestMapper = new WooDecisionRequestMapper();

        $wooDecision = $wooDecisionRequestMapper->create(
            $wooDecisionRequestDto,
            $organisation,
            $department,
            $subject,
            $dossierExternalId,
            $documentPrefix,
        );

        self::assertSame($dossierExternalId, $wooDecision->getExternalId());
        self::assertSame(DossierStatus::CONCEPT, $wooDecision->getStatus());
        self::assertSame($documentPrefix, $wooDecision->getDocumentPrefix());
        self::assertSame($dateFrom, $wooDecision->getDateFrom());
        self::assertSame($dateTo, $wooDecision->getDateTo());
        self::assertSame($previewDate, $wooDecision->getPreviewDate());
        self::assertSame($publicationDate, $wooDecision->getPublicationDate());
        self::assertSame($dossierNumber, $wooDecision->getDossierNumber());
        self::assertSame($summary, $wooDecision->getSummary());
        self::assertSame($title, $wooDecision->getTitle());
        self::assertSame($organisation, $wooDecision->getOrganisation());
        self::assertSame($subject, $wooDecision->getSubject());
        self::assertSame([$department], $wooDecision->getDepartments()->getValues());
    }
}
