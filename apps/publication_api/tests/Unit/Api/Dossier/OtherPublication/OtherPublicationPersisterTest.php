<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\OtherPublication;

use Mockery;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationPersister;
use PublicationApi\Api\Dossier\OtherPublication\OtherPublicationSnapshot;
use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationMainDocument;
use Shared\Tests\Unit\UnitTestCase;

final class OtherPublicationPersisterTest extends UnitTestCase
{
    public function testSnapshot(): void
    {
        $otherPublication = new OtherPublication();
        $otherPublication->setMainDocument(
            new OtherPublicationMainDocument(
                $otherPublication,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(OtherPublicationMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $otherPublicationPersister = new OtherPublicationPersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $otherPublicationSnapshot = $otherPublicationPersister->snapshot($otherPublication);

        self::assertInstanceOf(MetadataSnapshot::class, $otherPublicationSnapshot->mainDocument);
    }

    public function testSnapshotWithoutMainDocument(): void
    {
        $otherPublicationPersister = new OtherPublicationPersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $otherPublicationSnapshot = $otherPublicationPersister->snapshot(new OtherPublication());

        self::assertNull($otherPublicationSnapshot->mainDocument);
    }

    public function testPersist(): void
    {
        $otherPublication = new OtherPublication();
        $otherPublication->setStatus(DossierStatus::CONCEPT);

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($otherPublication);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($otherPublication);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($otherPublication);
        $dossierSupportService->expects('dispatchDossierCreatedEvent')->with($otherPublication);
        $dossierSupportService->expects('synchronizeArtifacts')->with($otherPublication);

        $otherPublicationPersister = new OtherPublicationPersister($dossierSupportService, $dossierValidator);

        $otherPublicationPersister->persist($otherPublication, null, []);
    }

    public function testPersistWithMainDocument(): void
    {
        $otherPublication = new OtherPublication();
        $otherPublication->setStatus(DossierStatus::PUBLISHED);
        $otherPublication->setMainDocument(
            new OtherPublicationMainDocument(
                $otherPublication,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(OtherPublicationMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $otherPublicationSnapshot = OtherPublicationSnapshot::of($otherPublication);
        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($otherPublication);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($otherPublication);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($otherPublication);
        $dossierSupportService->expects('dispatchPublicationEvents')
            ->with($otherPublication, $otherPublicationSnapshot->mainDocument, $attachmentEvents);
        $dossierSupportService->expects('synchronizeArtifacts')->with($otherPublication);

        $otherPublicationPersister = new OtherPublicationPersister($dossierSupportService, $dossierValidator);

        $otherPublicationPersister->persist($otherPublication, $otherPublicationSnapshot, $attachmentEvents);
    }
}
