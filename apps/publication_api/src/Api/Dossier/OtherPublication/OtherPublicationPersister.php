<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublication;

readonly class OtherPublicationPersister
{
    public function __construct(
        private DossierSupportService $dossierSupportService,
        private DossierValidator $dossierValidator,
    ) {
    }

    public function snapshot(OtherPublication $otherPublication): OtherPublicationSnapshot
    {
        return OtherPublicationSnapshot::of($otherPublication);
    }

    /**
     * @param list<AbstractAttachmentEvent> $attachmentEvents
     */
    public function persist(
        OtherPublication $otherPublication,
        ?OtherPublicationSnapshot $otherPublicationSnapshot,
        array $attachmentEvents,
    ): void {
        $this->dossierValidator->validateDossier($otherPublication);
        $this->dossierSupportService->autoPublish($otherPublication);
        $this->dossierSupportService->validateCompletionAndPersist($otherPublication);

        if ($otherPublicationSnapshot === null) {
            $this->dossierSupportService->dispatchDossierCreatedEvent($otherPublication);
        } else {
            $this->dossierSupportService->dispatchPublicationEvents($otherPublication, $otherPublicationSnapshot->mainDocument, $attachmentEvents);
        }

        $this->dossierSupportService->synchronizeArtifacts($otherPublication);
    }
}
