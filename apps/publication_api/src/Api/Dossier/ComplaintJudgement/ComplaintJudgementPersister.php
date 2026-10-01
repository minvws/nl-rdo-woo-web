<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\ComplaintJudgement;

use PublicationApi\Api\Dossier\DossierSupportService;
use PublicationApi\Api\Dossier\DossierValidator;
use Shared\Domain\Publication\Dossier\Type\ComplaintJudgement\ComplaintJudgement;

readonly class ComplaintJudgementPersister
{
    public function __construct(
        private DossierSupportService $dossierSupportService,
        private DossierValidator $dossierValidator,
    ) {
    }

    public function snapshot(ComplaintJudgement $complaintJudgement): ComplaintJudgementSnapshot
    {
        return ComplaintJudgementSnapshot::of($complaintJudgement);
    }

    public function persist(
        ComplaintJudgement $complaintJudgement,
        ?ComplaintJudgementSnapshot $complaintJudgementSnapshot,
    ): void {
        $this->dossierValidator->validateDossier($complaintJudgement);
        $this->dossierSupportService->autoPublish($complaintJudgement);
        $this->dossierSupportService->validateCompletionAndPersist($complaintJudgement);

        if ($complaintJudgementSnapshot === null) {
            $this->dossierSupportService->dispatchDossierCreatedEvent($complaintJudgement);
        } else {
            $this->dossierSupportService->dispatchPublicationEvents($complaintJudgement, $complaintJudgementSnapshot->mainDocument, []);
        }

        $this->dossierSupportService->synchronizeArtifacts($complaintJudgement);
    }
}
