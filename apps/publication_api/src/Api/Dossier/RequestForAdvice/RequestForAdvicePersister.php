<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use Shared\Domain\Publication\Attachment\Event\AbstractAttachmentEvent;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdvice;

readonly class RequestForAdvicePersister
{
    public function __construct(
        private DossierSupportService $dossierSupportService,
        private DossierValidator $dossierValidator,
    ) {
    }

    public function snapshot(RequestForAdvice $requestForAdvice): RequestForAdviceSnapshot
    {
        return RequestForAdviceSnapshot::of($requestForAdvice);
    }

    /**
     * @param list<AbstractAttachmentEvent> $attachmentEvents
     */
    public function persist(
        RequestForAdvice $requestForAdvice,
        ?RequestForAdviceSnapshot $requestForAdviceSnapshot,
        array $attachmentEvents,
    ): void {
        $this->dossierValidator->validateDossier($requestForAdvice);
        $this->dossierSupportService->autoPublish($requestForAdvice);
        $this->dossierSupportService->validateCompletionAndPersist($requestForAdvice);

        if ($requestForAdviceSnapshot === null) {
            $this->dossierSupportService->dispatchDossierCreatedEvent($requestForAdvice);
        } else {
            $this->dossierSupportService->dispatchPublicationEvents($requestForAdvice, $requestForAdviceSnapshot->mainDocument, $attachmentEvents);
        }

        $this->dossierSupportService->synchronizeArtifacts($requestForAdvice);
    }
}
