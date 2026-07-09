<?php

declare(strict_types=1);

namespace PublicationApi\FeatureFlag;

use ApiPlatform\Validator\Exception\ValidationException;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\ConstraintViolationList;

readonly class DocumentUploadGuard
{
    public function __construct(
        #[Autowire(param: 'enable_upload_document_for_published_dossier_via_api')]
        private bool $enableUploadDocumentForPublishedDossierViaApi = false,
    ) {
    }

    public function assertDocumentUploadIsAllowed(AbstractDossier $dossier): void
    {
        if ($this->enableUploadDocumentForPublishedDossierViaApi) {
            return;
        }

        if (! $dossier->getStatus()->isNewOrConcept()) {
            throw new ValidationException(
                ConstraintViolationList::createFromMessage('document upload is not allowed for a published dossier'),
            );
        }
    }
}
