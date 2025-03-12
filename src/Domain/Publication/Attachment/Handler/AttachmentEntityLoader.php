<?php

declare(strict_types=1);

namespace App\Domain\Publication\Attachment\Handler;

use App\Domain\Publication\Attachment\Entity\AbstractAttachment;
use App\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use App\Domain\Publication\Attachment\Exception\AttachmentNotFoundException;
use App\Domain\Publication\Attachment\Repository\AttachmentRepository;
use App\Domain\Publication\Dossier\AbstractDossier;
use App\Domain\Publication\Dossier\DossierRepository;
use App\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use App\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

readonly class AttachmentEntityLoader
{
    public function __construct(
        private DossierWorkflowManager $dossierWorkflowManager,
        private AttachmentRepository $attachmentRepository,
        private DossierRepository $dossierRepository,
    ) {
    }

    public function loadAndValidateAttachment(
        Uuid $dossierId,
        Uuid $attachmentId,
        DossierStatusTransition $transition,
    ): AbstractAttachment {
        $dossier = $this->loadDossier($dossierId);
        $attachment = $this->attachmentRepository->findOneOrNullForDossier($dossierId, $attachmentId);
        if ($attachment === null) {
            throw new AttachmentNotFoundException();
        }

        $this->dossierWorkflowManager->applyTransition($dossier, $transition);

        return $attachment;
    }

    public function loadAndValidateDossier(
        Uuid $dossierId,
        DossierStatusTransition $transition,
    ): AbstractDossier&EntityWithAttachments {
        $dossier = $this->loadDossier($dossierId);

        $this->dossierWorkflowManager->applyTransition($dossier, $transition);

        return $dossier;
    }

    private function loadDossier(Uuid $dossierId): EntityWithAttachments&AbstractDossier
    {
        /** @var AbstractDossier&EntityWithAttachments $dossier */
        $dossier = $this->dossierRepository->findOneByDossierId($dossierId);
        Assert::isInstanceOf($dossier, EntityWithAttachments::class);

        return $dossier;
    }
}
