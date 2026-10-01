<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\AnnualReport;

use Mockery;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportPersister;
use PublicationApi\Api\Dossier\AnnualReport\AnnualReportSnapshot;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReport;
use Shared\Domain\Publication\Dossier\Type\AnnualReport\AnnualReportMainDocument;
use Shared\Tests\Unit\UnitTestCase;

final class AnnualReportPersisterTest extends UnitTestCase
{
    public function testSnapshot(): void
    {
        $annualReport = new AnnualReport();
        $annualReport->setMainDocument(
            new AnnualReportMainDocument(
                $annualReport,
                $this->getFaker()->plainDate(),
                AttachmentType::ANNUAL_REPORT,
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $annualReportPersister = new AnnualReportPersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $annualReportSnapshot = $annualReportPersister->snapshot($annualReport);

        self::assertInstanceOf(MetadataSnapshot::class, $annualReportSnapshot->mainDocument);
    }

    public function testSnapshotWithoutMainDocument(): void
    {
        $annualReportPersister = new AnnualReportPersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $annualReportSnapshot = $annualReportPersister->snapshot(new AnnualReport());

        self::assertNull($annualReportSnapshot->mainDocument);
    }

    public function testPersist(): void
    {
        $annualReport = new AnnualReport();
        $annualReport->setStatus(DossierStatus::CONCEPT);

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($annualReport);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($annualReport);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($annualReport);
        $dossierSupportService->expects('dispatchDossierCreatedEvent')->with($annualReport);
        $dossierSupportService->expects('synchronizeArtifacts')->with($annualReport);

        $annualReportPersister = new AnnualReportPersister($dossierSupportService, $dossierValidator);

        $annualReportPersister->persist($annualReport, null, []);
    }

    public function testPersistWithMainDocument(): void
    {
        $annualReport = new AnnualReport();
        $annualReport->setStatus(DossierStatus::PUBLISHED);
        $annualReport->setMainDocument(
            new AnnualReportMainDocument(
                $annualReport,
                $this->getFaker()->plainDate(),
                AttachmentType::ANNUAL_REPORT,
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $annualReportSnapshot = AnnualReportSnapshot::of($annualReport);
        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($annualReport);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($annualReport);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($annualReport);
        $dossierSupportService->expects('dispatchPublicationEvents')->with($annualReport, $annualReportSnapshot->mainDocument, $attachmentEvents);
        $dossierSupportService->expects('synchronizeArtifacts')->with($annualReport);

        $annualReportPersister = new AnnualReportPersister($dossierSupportService, $dossierValidator);

        $annualReportPersister->persist($annualReport, $annualReportSnapshot, $attachmentEvents);
    }
}
