<?php

declare(strict_types=1);

namespace PublicationApi\Tests\Unit\Api\Dossier\RequestForAdvice;

use Mockery;
use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdvicePersister;
use PublicationApi\Api\Dossier\RequestForAdvice\RequestForAdviceSnapshot;
use PublicationApi\Domain\Dossier\MetadataSnapshot;
use Shared\Domain\Publication\Attachment\Event\AttachmentDeletedEvent;
use Shared\Domain\Publication\Dossier\DossierStatus;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceMainDocument;
use Shared\Tests\Unit\UnitTestCase;

final class RequestForAdvicePersisterTest extends UnitTestCase
{
    public function testSnapshot(): void
    {
        $requestForAdvice = new RequestForAdvice();
        $requestForAdvice->setMainDocument(
            new RequestForAdviceMainDocument(
                $requestForAdvice,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(RequestForAdviceMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $requestForAdvicePersister = new RequestForAdvicePersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $requestForAdviceSnapshot = $requestForAdvicePersister->snapshot($requestForAdvice);

        self::assertInstanceOf(MetadataSnapshot::class, $requestForAdviceSnapshot->mainDocument);
    }

    public function testSnapshotWithoutMainDocument(): void
    {
        $requestForAdvicePersister = new RequestForAdvicePersister(
            Mockery::mock(DossierSupportService::class),
            Mockery::mock(DossierValidator::class),
        );

        $requestForAdviceSnapshot = $requestForAdvicePersister->snapshot(new RequestForAdvice());

        self::assertNull($requestForAdviceSnapshot->mainDocument);
    }

    public function testPersist(): void
    {
        $requestForAdvice = new RequestForAdvice();
        $requestForAdvice->setStatus(DossierStatus::CONCEPT);

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($requestForAdvice);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($requestForAdvice);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($requestForAdvice);
        $dossierSupportService->expects('dispatchDossierCreatedEvent')->with($requestForAdvice);
        $dossierSupportService->expects('synchronizeArtifacts')->with($requestForAdvice);

        $requestForAdvicePersister = new RequestForAdvicePersister($dossierSupportService, $dossierValidator);

        $requestForAdvicePersister->persist($requestForAdvice, null, []);
    }

    public function testPersistWithMainDocument(): void
    {
        $requestForAdvice = new RequestForAdvice();
        $requestForAdvice->setStatus(DossierStatus::PUBLISHED);
        $requestForAdvice->setMainDocument(
            new RequestForAdviceMainDocument(
                $requestForAdvice,
                $this->getFaker()->plainDate(),
                $this->getFaker()->attachmentType(RequestForAdviceMainDocument::getAllowedTypes()),
                $this->getFaker()->attachmentLanguage(),
            ),
        );

        $requestForAdviceSnapshot = RequestForAdviceSnapshot::of($requestForAdvice);
        $attachmentEvents = [Mockery::mock(AttachmentDeletedEvent::class)];

        $dossierValidator = Mockery::mock(DossierValidator::class);
        $dossierValidator->expects('validateDossier')->with($requestForAdvice);

        $dossierSupportService = Mockery::mock(DossierSupportService::class);
        $dossierSupportService->expects('autoPublish')->with($requestForAdvice);
        $dossierSupportService->expects('validateCompletionAndPersist')->with($requestForAdvice);
        $dossierSupportService->expects('dispatchPublicationEvents')
            ->with($requestForAdvice, $requestForAdviceSnapshot->mainDocument, $attachmentEvents);
        $dossierSupportService->expects('synchronizeArtifacts')->with($requestForAdvice);

        $requestForAdvicePersister = new RequestForAdvicePersister($dossierSupportService, $dossierValidator);

        $requestForAdvicePersister->persist($requestForAdvice, $requestForAdviceSnapshot, $attachmentEvents);
    }
}
