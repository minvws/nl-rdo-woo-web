<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\Advice;

use Mockery;
use PublicationApi\Api\Dossier\Advice\AdvicePersister;
use PublicationApi\Api\Dossier\Advice\AdviceSnapshot;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceMainDocument;
use Shared\Tests\Unit\UnitTestCase;

final class AdvicePersisterTest extends UnitTestCase
{
    public function testSnapshot(): void
    {
        $advice = new Advice();
        $advice->setMainDocument(
            new AdviceMainDocument(
                $advice,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(AdviceMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $advicePersister = new AdvicePersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $adviceSnapshot = $advicePersister->snapshot($advice);

        self::assertInstanceOf(MetadataSnapshot::class, $adviceSnapshot->mainDocument);
    }

    public function testSnapshotWithoutMainDocument(): void
    {
        $advicePersister = new AdvicePersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $adviceSnapshot = $advicePersister->snapshot(new Advice());

        self::assertNull($adviceSnapshot->mainDocument);
    }

    public function testPersist(): void
    {
        $advice = new Advice();
        $advice->setStatus(DossierStatus::CONCEPT);

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($advice);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($advice);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($advice);
        $dossierSupportService->expects('dispatchDossierCreatedEvent')->with($advice);
        $dossierSupportService->expects('synchronizeArtifacts')->with($advice);

        $advicePersister = new AdvicePersister($dossierSupportService, $dossierValidator);

        $advicePersister->persist($advice, null, []);
    }

    public function testPersistWithMainDocument(): void
    {
        $advice = new Advice();
        $advice->setStatus(DossierStatus::PUBLISHED);
        $advice->setMainDocument(
            new AdviceMainDocument(
                $advice,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(AdviceMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $adviceSnapshot = AdviceSnapshot::of($advice);
        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($advice);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($advice);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($advice);
        $dossierSupportService->expects('dispatchPublicationEvents')->with($advice, $adviceSnapshot->mainDocument, $attachmentEvents);
        $dossierSupportService->expects('synchronizeArtifacts')->with($advice);

        $advicePersister = new AdvicePersister($dossierSupportService, $dossierValidator);

        $advicePersister->persist($advice, $adviceSnapshot, $attachmentEvents);
    }
}
