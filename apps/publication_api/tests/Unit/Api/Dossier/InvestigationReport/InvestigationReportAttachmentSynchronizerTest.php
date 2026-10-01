<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\InvestigationReport;

use Mockery;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\DossierAttachmentValidator;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportAttachmentSynchronizer;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Tests\Unit\UnitTestCase;

final class InvestigationReportAttachmentSynchronizerTest extends UnitTestCase
{
    public function testCreate(): void
    {
        $attachmentRequestDtos = [
            new AttachmentRequestDto(
                $this->getFaker()->fileName(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentLanguage(),
                $this->getFaker()->attachmentType(),
                $this->getFaker()->externalId(),
            ),
        ];

        $investigationReport = new InvestigationReport();
        $investigationReport->setStatus(DossierStatus::CONCEPT);

        $attachmentEvents = [];

        $attachmentSynchronizer = Mockery::mock(AttachmentSynchronizer::class);
        $attachmentSynchronizer->expects('sync')->with($investigationReport, $attachmentRequestDtos)->andReturn($attachmentEvents);

        $dossierAttachmentValidator = Mockery::mock(DossierAttachmentValidator::class);
        $dossierAttachmentValidator->expects('assertUniqueExternalIds')->with($attachmentRequestDtos);
        $dossierAttachmentValidator->expects('validate')->with([], DossierStatus::CONCEPT);

        $investigationReportAttachmentSynchronizer = new InvestigationReportAttachmentSynchronizer(
            $attachmentSynchronizer,
            $dossierAttachmentValidator,
        );

        self::assertSame($attachmentEvents, $investigationReportAttachmentSynchronizer->create($investigationReport, $attachmentRequestDtos));
    }

    public function testUpdate(): void
    {
        $attachmentRequestDtos = [
            new AttachmentRequestDto(
                $this->getFaker()->fileName(),
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentLanguage(),
                $this->getFaker()->attachmentType(),
                $this->getFaker()->externalId(),
            ),
        ];

        $investigationReport = new InvestigationReport();
        $investigationReport->setStatus(DossierStatus::PUBLISHED);

        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $attachmentSynchronizer = Mockery::mock(AttachmentSynchronizer::class);
        $attachmentSynchronizer->expects('sync')->with($investigationReport, $attachmentRequestDtos)->andReturn($attachmentEvents);

        $dossierAttachmentValidator = Mockery::mock(DossierAttachmentValidator::class);
        $dossierAttachmentValidator->expects('assertUniqueExternalIds')->with($attachmentRequestDtos);
        $dossierAttachmentValidator->expects('assertNoAttachmentRemovalInNonConcept')->with($investigationReport, $attachmentRequestDtos);
        $dossierAttachmentValidator->expects('validate')->with([], DossierStatus::PUBLISHED);

        $investigationReportAttachmentSynchronizer = new InvestigationReportAttachmentSynchronizer(
            $attachmentSynchronizer,
            $dossierAttachmentValidator,
        );

        self::assertSame($attachmentEvents, $investigationReportAttachmentSynchronizer->update($investigationReport, $attachmentRequestDtos));
    }
}
