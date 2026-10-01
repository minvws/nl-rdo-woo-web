<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\InvestigationReport;

use Mockery;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportPersister;
use PublicationApi\Api\Dossier\InvestigationReport\InvestigationReportSnapshot;
use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReport;
use Shared\Domain\Publication\Dossier\Type\InvestigationReport\InvestigationReportMainDocument;
use Shared\Tests\Unit\UnitTestCase;

final class InvestigationReportPersisterTest extends UnitTestCase
{
    public function testSnapshot(): void
    {
        $investigationReport = new InvestigationReport();
        $investigationReport->setMainDocument(
            new InvestigationReportMainDocument(
                $investigationReport,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(InvestigationReportMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $investigationReportPersister = new InvestigationReportPersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $investigationReportSnapshot = $investigationReportPersister->snapshot($investigationReport);

        self::assertInstanceOf(MetadataSnapshot::class, $investigationReportSnapshot->mainDocument);
    }

    public function testSnapshotWithoutMainDocument(): void
    {
        $investigationReportPersister = new InvestigationReportPersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $investigationReportSnapshot = $investigationReportPersister->snapshot(new InvestigationReport());

        self::assertNull($investigationReportSnapshot->mainDocument);
    }

    public function testPersist(): void
    {
        $investigationReport = new InvestigationReport();
        $investigationReport->setStatus(DossierStatus::CONCEPT);

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($investigationReport);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($investigationReport);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($investigationReport);
        $dossierSupportService->expects('dispatchDossierCreatedEvent')->with($investigationReport);
        $dossierSupportService->expects('synchronizeArtifacts')->with($investigationReport);

        $investigationReportPersister = new InvestigationReportPersister($dossierSupportService, $dossierValidator);

        $investigationReportPersister->persist($investigationReport, null, []);
    }

    public function testPersistWithMainDocument(): void
    {
        $investigationReport = new InvestigationReport();
        $investigationReport->setStatus(DossierStatus::PUBLISHED);
        $investigationReport->setMainDocument(
            new InvestigationReportMainDocument(
                $investigationReport,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(InvestigationReportMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $investigationReportSnapshot = InvestigationReportSnapshot::of($investigationReport);
        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($investigationReport);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($investigationReport);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($investigationReport);
        $dossierSupportService->expects('dispatchPublicationEvents')
            ->with($investigationReport, $investigationReportSnapshot->mainDocument, $attachmentEvents);
        $dossierSupportService->expects('synchronizeArtifacts')->with($investigationReport);

        $investigationReportPersister = new InvestigationReportPersister($dossierSupportService, $dossierValidator);

        $investigationReportPersister->persist($investigationReport, $investigationReportSnapshot, $attachmentEvents);
    }
}
