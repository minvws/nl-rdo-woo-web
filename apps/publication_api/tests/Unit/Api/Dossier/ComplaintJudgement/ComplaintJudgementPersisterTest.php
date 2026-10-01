<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\ComplaintJudgement;

use Mockery;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementPersister;
use PublicationApi\Api\Dossier\ComplaintJudgement\ComplaintJudgementSnapshot;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgementMainDocument;
use Shared\Tests\Unit\UnitTestCase;

final class ComplaintJudgementPersisterTest extends UnitTestCase
{
    public function testSnapshot(): void
    {
        $complaintJudgement = new ComplaintJudgement();
        $complaintJudgement->setMainDocument(
            new ComplaintJudgementMainDocument(
                $complaintJudgement,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(ComplaintJudgementMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $complaintJudgementPersister = new ComplaintJudgementPersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $complaintJudgementSnapshot = $complaintJudgementPersister->snapshot($complaintJudgement);

        self::assertInstanceOf(MetadataSnapshot::class, $complaintJudgementSnapshot->mainDocument);
    }

    public function testSnapshotWithoutMainDocument(): void
    {
        $complaintJudgementPersister = new ComplaintJudgementPersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $complaintJudgementSnapshot = $complaintJudgementPersister->snapshot(new ComplaintJudgement());

        self::assertNull($complaintJudgementSnapshot->mainDocument);
    }

    public function testPersist(): void
    {
        $complaintJudgement = new ComplaintJudgement();
        $complaintJudgement->setStatus(DossierStatus::CONCEPT);

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($complaintJudgement);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($complaintJudgement);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($complaintJudgement);
        $dossierSupportService->expects('dispatchDossierCreatedEvent')->with($complaintJudgement);
        $dossierSupportService->expects('synchronizeArtifacts')->with($complaintJudgement);

        $complaintJudgementPersister = new ComplaintJudgementPersister($dossierSupportService, $dossierValidator);

        $complaintJudgementPersister->persist($complaintJudgement, null);
    }

    public function testPersistWithMainDocument(): void
    {
        $complaintJudgement = new ComplaintJudgement();
        $complaintJudgement->setStatus(DossierStatus::PUBLISHED);
        $complaintJudgement->setMainDocument(
            new ComplaintJudgementMainDocument(
                $complaintJudgement,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(ComplaintJudgementMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $complaintJudgementSnapshot = ComplaintJudgementSnapshot::of($complaintJudgement);
        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($complaintJudgement);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($complaintJudgement);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($complaintJudgement);
        $dossierSupportService->expects('dispatchPublicationEvents')
            ->with($complaintJudgement, $complaintJudgementSnapshot->mainDocument, []);
        $dossierSupportService->expects('synchronizeArtifacts')->with($complaintJudgement);

        $complaintJudgementPersister = new ComplaintJudgementPersister($dossierSupportService, $dossierValidator);

        $complaintJudgementPersister->persist($complaintJudgement, $complaintJudgementSnapshot);
    }
}
