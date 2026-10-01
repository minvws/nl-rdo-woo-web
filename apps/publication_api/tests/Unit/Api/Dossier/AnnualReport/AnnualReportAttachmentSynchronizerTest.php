<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\AnnualReport;

use Mockery;
use PublicationApi\Api\Attachment\AttachmentRequestDto;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportAttachmentSynchronizer;
use PublicationApi\Api\Dossier\DossierAttachmentValidator;
use PublicationApi\Domain\Dossier\AttachmentSynchronizer;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Tests\Unit\UnitTestCase;

final class AnnualReportAttachmentSynchronizerTest extends UnitTestCase
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

        $annualReport = new AnnualReport();
        $annualReport->setStatus(DossierStatus::CONCEPT);

        $attachmentEvents = [];

        $attachmentSynchronizer = Mockery::mock(AttachmentSynchronizer::class);
        $attachmentSynchronizer->expects('sync')->with($annualReport, $attachmentRequestDtos)->andReturn($attachmentEvents);

        $dossierAttachmentValidator = Mockery::mock(DossierAttachmentValidator::class);
        $dossierAttachmentValidator->expects('assertUniqueExternalIds')->with($attachmentRequestDtos);
        $dossierAttachmentValidator->expects('validate')->with([], DossierStatus::CONCEPT);

        $annualReportAttachmentSynchronizer = new AnnualReportAttachmentSynchronizer($attachmentSynchronizer, $dossierAttachmentValidator);

        self::assertSame($attachmentEvents, $annualReportAttachmentSynchronizer->create($annualReport, $attachmentRequestDtos));
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

        $annualReport = new AnnualReport();
        $annualReport->setStatus(DossierStatus::PUBLISHED);

        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $attachmentSynchronizer = Mockery::mock(AttachmentSynchronizer::class);
        $attachmentSynchronizer->expects('sync')->with($annualReport, $attachmentRequestDtos)->andReturn($attachmentEvents);

        $dossierAttachmentValidator = Mockery::mock(DossierAttachmentValidator::class);
        $dossierAttachmentValidator->expects('assertUniqueExternalIds')->with($attachmentRequestDtos);
        $dossierAttachmentValidator->expects('assertNoAttachmentRemovalInNonConcept')->with($annualReport, $attachmentRequestDtos);
        $dossierAttachmentValidator->expects('validate')->with([], DossierStatus::PUBLISHED);

        $annualReportAttachmentSynchronizer = new AnnualReportAttachmentSynchronizer($attachmentSynchronizer, $dossierAttachmentValidator);

        self::assertSame($attachmentEvents, $annualReportAttachmentSynchronizer->update($annualReport, $attachmentRequestDtos));
    }
}
