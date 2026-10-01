<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\DraftDecision;

use Mockery;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionPersister;
use PublicationApi\Api\Dossier\DraftDecision\DraftDecisionSnapshot;
use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionMainDocument;
use Shared\Tests\Unit\UnitTestCase;

final class DraftDecisionPersisterTest extends UnitTestCase
{
    public function testSnapshot(): void
    {
        $draftDecision = new DraftDecision();
        $draftDecision->setMainDocument(
            new DraftDecisionMainDocument(
                $draftDecision,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(DraftDecisionMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $draftDecisionPersister = new DraftDecisionPersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $draftDecisionSnapshot = $draftDecisionPersister->snapshot($draftDecision);

        self::assertInstanceOf(MetadataSnapshot::class, $draftDecisionSnapshot->mainDocument);
    }

    public function testSnapshotWithoutMainDocument(): void
    {
        $draftDecisionPersister = new DraftDecisionPersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $draftDecisionSnapshot = $draftDecisionPersister->snapshot(new DraftDecision());

        self::assertNull($draftDecisionSnapshot->mainDocument);
    }

    public function testPersist(): void
    {
        $draftDecision = new DraftDecision();
        $draftDecision->setStatus(DossierStatus::CONCEPT);

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($draftDecision);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($draftDecision);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($draftDecision);
        $dossierSupportService->expects('dispatchDossierCreatedEvent')->with($draftDecision);
        $dossierSupportService->expects('synchronizeArtifacts')->with($draftDecision);

        $draftDecisionPersister = new DraftDecisionPersister($dossierSupportService, $dossierValidator);

        $draftDecisionPersister->persist($draftDecision, null, []);
    }

    public function testPersistWithMainDocument(): void
    {
        $draftDecision = new DraftDecision();
        $draftDecision->setStatus(DossierStatus::PUBLISHED);
        $draftDecision->setMainDocument(
            new DraftDecisionMainDocument(
                $draftDecision,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(DraftDecisionMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $draftDecisionSnapshot = DraftDecisionSnapshot::of($draftDecision);
        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($draftDecision);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($draftDecision);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($draftDecision);
        $dossierSupportService->expects('dispatchPublicationEvents')->with($draftDecision, $draftDecisionSnapshot->mainDocument, $attachmentEvents);
        $dossierSupportService->expects('synchronizeArtifacts')->with($draftDecision);

        $draftDecisionPersister = new DraftDecisionPersister($dossierSupportService, $dossierValidator);

        $draftDecisionPersister->persist($draftDecision, $draftDecisionSnapshot, $attachmentEvents);
    }
}
