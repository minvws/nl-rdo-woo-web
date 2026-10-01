<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\Type\Covenant\Covenant;

readonly class CovenantPersister
{
    public function __construct(
        private DossierSupportService $dossierSupportService,
        private DossierValidator $dossierValidator,
    ) {
    }

    public function snapshot(Covenant $covenant): CovenantSnapshot
    {
        return CovenantSnapshot::of($covenant);
    }

    /**
     * @param list<AbstractAttachmentEvent> $attachmentEvents
     */
    public function persist(
        Covenant $covenant,
        ?CovenantSnapshot $covenantSnapshot,
        array $attachmentEvents,
    ): void {
        $this->dossierValidator->validateDossier($covenant);
        $this->dossierSupportService->autoPublish($covenant);
        $this->dossierSupportService->validateCompletionAndPersist($covenant);

        if ($covenantSnapshot === null) {
            $this->dossierSupportService->dispatchDossierCreatedEvent($covenant);
        } else {
            $this->dossierSupportService->dispatchPublicationEvents($covenant, $covenantSnapshot->mainDocument, $attachmentEvents);
        }

        $this->dossierSupportService->synchronizeArtifacts($covenant);
    }
}
