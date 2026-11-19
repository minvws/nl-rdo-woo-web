<?php

declare(strict_types=1);

namespace PublicationApi\Api\Uploads\MainDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\DossierLookup;
use PublicationApi\Api\OrganisationLookup;
use Shared\Domain\Publication\Dossier\Type\DossierRepositoryWithExternalId;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Symfony\Component\Validator\ConstraintViolationList;

abstract readonly class UploadMainDocumentProcessor implements ProcessorInterface
{
    /**
     * @param class-string<AbstractMainDocument> $mainDocumentClass
     */
    public function __construct(
        private DossierLookup $dossierLookup,
        private OrganisationLookup $organisationLookup,
        private UploadMainDocumentHandler $uploadMainDocumentHandler,
        private DossierRepositoryWithExternalId $dossierRepositoryWithExternalId,
        private string $mainDocumentClass,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (! $data instanceof UploadMainDocumentRequestDto) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Invalid main document request'));
        }

        $organisation = $this->organisationLookup->find($data->getOrganisationId());
        $dossier = $this->dossierLookup->find($this->dossierRepositoryWithExternalId, $organisation, $data->getDossierExternalId());

        if (! $dossier instanceof EntityWithMainDocument) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No main document found'));
        }

        $mainDocument = $dossier->getMainDocument();
        if (! $mainDocument instanceof $this->mainDocumentClass) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No main document found'));
        }

        $this->uploadMainDocumentHandler->handle($dossier, $mainDocument, $data->getContent());
    }
}
