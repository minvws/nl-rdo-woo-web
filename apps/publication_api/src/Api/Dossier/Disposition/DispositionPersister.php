<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\Type\Disposition\Disposition;

readonly class DispositionPersister
{
    public function __construct(
        private DossierSupportService $dossierSupportService,
        private DossierValidator $dossierValidator,
    ) {
    }

    public function snapshot(Disposition $disposition): DispositionSnapshot
    {
        return DispositionSnapshot::of($disposition);
    }

    /**
     * @param list<AbstractAttachmentEvent> $attachmentEvents
     */
    public function persist(
        Disposition $disposition,
        ?DispositionSnapshot $dispositionSnapshot,
        array $attachmentEvents,
    ): void {
        $this->dossierValidator->validateDossier($disposition);
        $this->dossierSupportService->autoPublish($disposition);
        $this->dossierSupportService->validateCompletionAndPersist($disposition);

        if ($dispositionSnapshot === null) {
            $this->dossierSupportService->dispatchDossierCreatedEvent($disposition);
        } else {
            $this->dossierSupportService->dispatchPublicationEvents($disposition, $dispositionSnapshot->mainDocument, $attachmentEvents);
        }

        $this->dossierSupportService->synchronizeArtifacts($disposition);
    }
}
