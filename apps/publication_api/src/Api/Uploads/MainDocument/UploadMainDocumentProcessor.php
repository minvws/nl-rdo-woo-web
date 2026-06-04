<?php

declare(strict_types=1);

namespace PublicationApi\Api\Uploads\MainDocument;

use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\DossierLookup;
use PublicationApi\Api\OrganisationLookup;
use Shared\Domain\Publication\Dossier\Type\DossierRepositoryWithExternalId;
use Shared\Domain\Publication\MainDocument\AbstractMainDocument;
use Shared\Domain\Publication\MainDocument\EntityWithMainDocument;
use Symfony\Component\Validator\ConstraintViolationList;

readonly class UploadMainDocumentProcessor
{
    public function __construct(
        private DossierLookup $dossierLookup,
        private OrganisationLookup $organisationLookup,
        private UploadMainDocumentHandler $uploadMainDocumentHandler,
    ) {
    }

    /**
     * @param class-string<AbstractMainDocument> $mainDocumentClass
     */
    public function process(
        UploadMainDocumentRequestInterface $uploadMainDocumentRequest,
        DossierRepositoryWithExternalId $dossierRepositoryWithExternalId,
        string $mainDocumentClass,
    ): void {
        $organisation = $this->organisationLookup->find($uploadMainDocumentRequest->getOrganisationId());
        $dossier = $this->dossierLookup->find($dossierRepositoryWithExternalId, $organisation, $uploadMainDocumentRequest->getDossierExternalId());

        if (! $dossier instanceof EntityWithMainDocument) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No main document found'));
        }

        $mainDocument = $dossier->getMainDocument();
        if (! $mainDocument instanceof $mainDocumentClass) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No main document found'));
        }

        $this->uploadMainDocumentHandler->handle($dossier, $mainDocument, $uploadMainDocumentRequest->getContent());
    }
}
