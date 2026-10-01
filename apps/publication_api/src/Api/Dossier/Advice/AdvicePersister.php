<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\Type\Advice\Advice;

readonly class AdvicePersister
{
    public function __construct(
        private DossierSupportService $dossierSupportService,
        private DossierValidator $dossierValidator,
    ) {
    }

    public function snapshot(Advice $advice): AdviceSnapshot
    {
        return AdviceSnapshot::of($advice);
    }

    /**
     * @param list<AbstractAttachmentEvent> $attachmentEvents
     */
    public function persist(
        Advice $advice,
        ?AdviceSnapshot $adviceSnapshot,
        array $attachmentEvents,
    ): void {
        $this->dossierValidator->validateDossier($advice);
        $this->dossierSupportService->autoPublish($advice);
        $this->dossierSupportService->validateCompletionAndPersist($advice);

        if ($adviceSnapshot === null) {
            $this->dossierSupportService->dispatchDossierCreatedEvent($advice);
        } else {
            $this->dossierSupportService->dispatchPublicationEvents($advice, $adviceSnapshot->mainDocument, $attachmentEvents);
        }

        $this->dossierSupportService->synchronizeArtifacts($advice);
    }
}
