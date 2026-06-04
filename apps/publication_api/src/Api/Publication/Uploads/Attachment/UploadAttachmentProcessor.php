<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Uploads\Attachment;

use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Publication\DossierLookup;
use PublicationApi\Api\Publication\OrganisationLookup;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\Type\DossierRepositoryWithExternalId;
use Symfony\Component\Validator\ConstraintViolationList;

readonly class UploadAttachmentProcessor
{
    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private DossierLookup $dossierLookup,
        private OrganisationLookup $organisationLookup,
        private UploadAttachmentHandler $uploadAttachmentHandler,
    ) {
    }

    /**
     * @param class-string<AbstractAttachment> $attachmentClass
     */
    public function process(
        UploadAttachmentRequestInterface $uploadAttachmentRequest,
        DossierRepositoryWithExternalId $dossierRepositoryWithExternalId,
        string $attachmentClass,
    ): void {
        $organisation = $this->organisationLookup->find($uploadAttachmentRequest->getOrganisationId());
        $dossier = $this->dossierLookup->find($dossierRepositoryWithExternalId, $organisation, $uploadAttachmentRequest->getDossierExternalId());

        $attachment = $this->attachmentRepository->findByDossierAndExternalId($dossier, $uploadAttachmentRequest->getAttachmentExternalId());
        if (! $attachment instanceof $attachmentClass) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No attachment found for this dossier'));
        }

        $this->uploadAttachmentHandler->handle($dossier, $attachment, $uploadAttachmentRequest->getContent());
    }
}
