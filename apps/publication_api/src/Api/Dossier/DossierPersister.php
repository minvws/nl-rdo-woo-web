<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;

readonly class DossierPersister
{
    public function __construct(
        private DossierSupportService $dossierSupportService,
        private DossierValidator $dossierValidator,
    ) {
    }

    public function snapshot(EntityWithMainDocument $entityWithMainDocument): MainDocumentSnapshot
    {
        return MainDocumentSnapshot::of($entityWithMainDocument);
    }

    /**
     * @param list<AbstractAttachmentEvent> $attachmentEvents
     */
    public function persist(
        AbstractDossier $dossier,
        ?MainDocumentSnapshot $mainDocumentSnapshot,
        array $attachmentEvents,
    ): void {
        $this->dossierValidator->validateDossier($dossier);
        $this->dossierSupportService->autoPublish($dossier);
        $this->dossierSupportService->validateCompletionAndPersist($dossier);

        if (! $mainDocumentSnapshot instanceof MainDocumentSnapshot) {
            $this->dossierSupportService->dispatchDossierCreatedEvent($dossier);
        } else {
            $this->dossierSupportService->dispatchPublicationEvents($dossier, $mainDocumentSnapshot->mainDocument, $attachmentEvents);
        }

        $this->dossierSupportService->synchronizeArtifacts($dossier);
    }
}
