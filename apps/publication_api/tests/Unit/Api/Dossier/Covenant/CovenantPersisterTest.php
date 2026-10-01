<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Covenant;

use Mockery;
use PublicationApi\Api\Dossier\Covenant\CovenantPersister;
use PublicationApi\Api\Dossier\Covenant\CovenantSnapshot;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantMainDocument;
use Shared\Tests\Unit\UnitTestCase;

final class CovenantPersisterTest extends UnitTestCase
{
    public function testSnapshot(): void
    {
        $covenant = new Covenant();
        $covenant->setMainDocument(
            new CovenantMainDocument($covenant, $this->getFaker()->plainDate(), $this->getFaker()->attachmentLanguage()),
        );

        $covenantPersister = new CovenantPersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $covenantSnapshot = $covenantPersister->snapshot($covenant);

        self::assertInstanceOf(MetadataSnapshot::class, $covenantSnapshot->mainDocument);
    }

    public function testSnapshotWithoutMainDocument(): void
    {
        $covenantPersister = new CovenantPersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $covenantSnapshot = $covenantPersister->snapshot(new Covenant());

        self::assertNull($covenantSnapshot->mainDocument);
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

        $covenantPersister = new CovenantPersister($dossierSupportService, $dossierValidator);

        $covenantPersister->persist($covenant, null, []);
    }

    public function testPersistWithMainDocument(): void
    {
        $covenant = new Covenant();
        $covenant->setStatus(DossierStatus::PUBLISHED);
        $covenant->setMainDocument(
            new CovenantMainDocument($covenant, $this->getFaker()->plainDate(), $this->getFaker()->attachmentLanguage()),
        );

        $covenantSnapshot = CovenantSnapshot::of($covenant);
        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($covenant);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($covenant);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($covenant);
        $dossierSupportService->expects('dispatchPublicationEvents')->with($covenant, $covenantSnapshot->mainDocument, $attachmentEvents);
        $dossierSupportService->expects('synchronizeArtifacts')->with($covenant);

        $covenantPersister = new CovenantPersister($dossierSupportService, $dossierValidator);

        $covenantPersister->persist($covenant, $covenantSnapshot, $attachmentEvents);
    }
}
