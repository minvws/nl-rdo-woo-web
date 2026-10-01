<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Disposition;

use Mockery;
use PublicationApi\Api\Dossier\Disposition\DispositionPersister;
use PublicationApi\Api\Dossier\Disposition\DispositionSnapshot;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionMainDocument;
use Shared\Tests\Unit\UnitTestCase;

final class DispositionPersisterTest extends UnitTestCase
{
    public function testSnapshot(): void
    {
        $disposition = new Disposition();
        $disposition->setMainDocument(
            new DispositionMainDocument(
                $disposition,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $dispositionPersister = new DispositionPersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $dispositionSnapshot = $dispositionPersister->snapshot($disposition);

        self::assertInstanceOf(MetadataSnapshot::class, $dispositionSnapshot->mainDocument);
    }

    public function testSnapshotWithoutMainDocument(): void
    {
        $dispositionPersister = new DispositionPersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $dispositionSnapshot = $dispositionPersister->snapshot(new Disposition());

        self::assertNull($dispositionSnapshot->mainDocument);
    }

    public function testPersist(): void
    {
        $disposition = new Disposition();
        $disposition->setStatus(DossierStatus::CONCEPT);

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($disposition);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($disposition);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($disposition);
        $dossierSupportService->expects('dispatchDossierCreatedEvent')->with($disposition);
        $dossierSupportService->expects('synchronizeArtifacts')->with($disposition);

        $dispositionPersister = new DispositionPersister($dossierSupportService, $dossierValidator);

        $dispositionPersister->persist($disposition, null, []);
    }

    public function testPersistWithMainDocument(): void
    {
        $disposition = new Disposition();
        $disposition->setStatus(DossierStatus::PUBLISHED);
        $disposition->setMainDocument(
            new DispositionMainDocument(
                $disposition,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(DispositionMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $dispositionSnapshot = DispositionSnapshot::of($disposition);
        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($disposition);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($disposition);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($disposition);
        $dossierSupportService->expects('dispatchPublicationEvents')->with($disposition, $dispositionSnapshot->mainDocument, $attachmentEvents);
        $dossierSupportService->expects('synchronizeArtifacts')->with($disposition);

        $dispositionPersister = new DispositionPersister($dossierSupportService, $dossierValidator);

        $dispositionPersister->persist($disposition, $dispositionSnapshot, $attachmentEvents);
    }
}
