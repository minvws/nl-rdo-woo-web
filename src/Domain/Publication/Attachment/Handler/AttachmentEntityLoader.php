<?php

declare(strict_types=1);

namespace Shared\Domain\Publication\Attachment\Handler;

use Doctrine\ORM\NoResultException;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Entity\EntityWithAttachments;
use Shared\Domain\Publication\Attachment\Exception\AttachmentNotFoundException;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\DossierRepository;
use Shared\Domain\Publication\Dossier\Workflow\DossierStatusTransition;
use Shared\Domain\Publication\Dossier\Workflow\DossierWorkflowManager;
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

    public function loadAttachment(
        Uuid $dossierId,
        Uuid $attachmentId,
    ): AbstractAttachment {
        $dossier = $this->loadDossier($dossierId);

        return $this->doLoadAttachment($dossier, $attachmentId);
    }

    public function loadAndValidateAttachment(
        Uuid $dossierId,
        Uuid $attachmentId,
        DossierStatusTransition $transition,
    ): AbstractAttachment {
        $dossier = $this->loadDossier($dossierId);
        $attachment = $this->doLoadAttachment($dossier, $attachmentId);

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

    private function doLoadAttachment(EntityWithAttachments&AbstractDossier $dossier, Uuid $attachmentId): AbstractAttachment
    {
        try {
            $attachment = $this->attachmentRepository->findOneForDossier($dossier->getId(), $attachmentId);
        } catch (NoResultException $e) {
            throw new AttachmentNotFoundException($e);
        }

        return $attachment;
    }
}
