<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier;

use Mockery;
use PublicationApi\Api\Dossier\DossierPersister;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use PublicationApi\Api\Dossier\MainDocumentSnapshot;
use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Tests\Unit\UnitTestCase;

final class DossierPersisterTest extends UnitTestCase
{
    public function testSnapshot(): void
    {
        $covenant = new Covenant();
        $covenant->setMainDocument(
            new CovenantMainDocument($covenant, $this->getFaker()->plainDate(), $this->getFaker()->attachmentLanguage()),
        );

        $dossierPersister = new DossierPersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $mainDocumentSnapshot = $dossierPersister->snapshot($covenant);

        self::assertInstanceOf(MetadataSnapshot::class, $mainDocumentSnapshot->mainDocument);
    }

    public function testSnapshotWithoutMainDocument(): void
    {
        $dossierPersister = new DossierPersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $mainDocumentSnapshot = $dossierPersister->snapshot(new Covenant());

        self::assertNull($mainDocumentSnapshot->mainDocument);
    }

    public function testPersist(): void
    {
        $covenant = new Covenant();
        $covenant->setStatus(DossierStatus::CONCEPT);

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($covenant);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($covenant);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($covenant);
        $dossierSupportService->expects('dispatchDossierCreatedEvent')->with($covenant);
        $dossierSupportService->expects('synchronizeArtifacts')->with($covenant);

        $dossierPersister = new DossierPersister($dossierSupportService, $dossierValidator);

        $dossierPersister->persist($covenant, null, []);
    }

    public function testPersistWithMainDocument(): void
    {
        $covenant = new Covenant();
        $covenant->setStatus(DossierStatus::PUBLISHED);
        $covenant->setMainDocument(
            new CovenantMainDocument($covenant, $this->getFaker()->plainDate(), $this->getFaker()->attachmentLanguage()),
        );

        $mainDocumentSnapshot = MainDocumentSnapshot::of($covenant);
        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($covenant);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($covenant);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($covenant);
        $dossierSupportService->expects('dispatchPublicationEvents')->with($covenant, $mainDocumentSnapshot->mainDocument, $attachmentEvents);
        $dossierSupportService->expects('synchronizeArtifacts')->with($covenant);

        $dossierPersister = new DossierPersister($dossierSupportService, $dossierValidator);

        $dossierPersister->persist($covenant, $mainDocumentSnapshot, $attachmentEvents);
    }
}
