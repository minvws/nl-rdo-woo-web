<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecision;

readonly class DraftDecisionPersister
{
    public function __construct(
        private DossierSupportService $dossierSupportService,
        private DossierValidator $dossierValidator,
    ) {
    }

    public function snapshot(DraftDecision $draftDecision): DraftDecisionSnapshot
    {
        return DraftDecisionSnapshot::of($draftDecision);
    }

    /**
     * @param list<AbstractAttachmentEvent> $attachmentEvents
     */
    public function persist(
        DraftDecision $draftDecision,
        ?DraftDecisionSnapshot $draftDecisionSnapshot,
        array $attachmentEvents,
    ): void {
        $this->dossierValidator->validateDossier($draftDecision);
        $this->dossierSupportService->autoPublish($draftDecision);
        $this->dossierSupportService->validateCompletionAndPersist($draftDecision);

        if ($draftDecisionSnapshot === null) {
            $this->dossierSupportService->dispatchDossierCreatedEvent($draftDecision);
        } else {
            $this->dossierSupportService->dispatchPublicationEvents($draftDecision, $draftDecisionSnapshot->mainDocument, $attachmentEvents);
        }

        $this->dossierSupportService->synchronizeArtifacts($draftDecision);
    }
}
