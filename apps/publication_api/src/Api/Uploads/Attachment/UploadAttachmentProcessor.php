<?php

declare(strict_types=1);

namespace PublicationApi\Api\Uploads\Attachment;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\DossierLookup;
use PublicationApi\Api\OrganisationLookup;
use Shared\Domain\Publication\Attachment\Entity\AbstractAttachment;
use Shared\Domain\Publication\Attachment\Repository\AttachmentRepository;
use Shared\Domain\Publication\Dossier\Type\DossierRepositoryWithExternalId;
use Symfony\Component\Validator\ConstraintViolationList;

abstract readonly class UploadAttachmentProcessor implements ProcessorInterface
{
    /**
     * @param class-string<AbstractAttachment> $attachmentClass
     */
    public function __construct(
        private AttachmentRepository $attachmentRepository,
        private DossierLookup $dossierLookup,
        private OrganisationLookup $organisationLookup,
        private UploadAttachmentHandler $uploadAttachmentHandler,
        private DossierRepositoryWithExternalId $dossierRepositoryWithExternalId,
        private string $attachmentClass,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (! $data instanceof UploadAttachmentRequestDto) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Invalid attachment request'));
        }

        $organisation = $this->organisationLookup->find($data->getOrganisationId());
        $dossier = $this->dossierLookup->find($this->dossierRepositoryWithExternalId, $organisation, $data->getDossierExternalId());

        $attachment = $this->attachmentRepository->findByDossierAndExternalId($dossier, $data->getAttachmentExternalId());
        if (! $attachment instanceof $this->attachmentClass) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No attachment found for this dossier'));
        }

        $this->uploadAttachmentHandler->handle($dossier, $attachment, $data->getContent());
    }
}
