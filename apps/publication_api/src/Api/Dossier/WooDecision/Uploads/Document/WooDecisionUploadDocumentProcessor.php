<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Uploads\Document;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Uploads\Document\UploadDocumentHandler;
use PublicationApi\Api\Uploads\Document\UploadDocumentRequestInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Symfony\Component\Validator\ConstraintViolationList;

readonly class WooDecisionUploadDocumentProcessor implements ProcessorInterface
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private OrganisationRepository $organisationRepository,
        private UploadDocumentHandler $uploadDocumentHandler,
        private WooDecisionRepository $wooDecisionRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): void
    {
        if (! $data instanceof UploadDocumentRequestInterface) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Invalid document request'));
        }

        $organisation = $this->organisationRepository->find($data->getOrganisationId());
        if (! $organisation instanceof Organisation) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No organisation found'));
        }

        $dossier = $this->wooDecisionRepository->findByOrganisationAndExternalId($organisation, $data->getDossierExternalId());
        if (! $dossier instanceof WooDecision) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No dossier found for this organisation'));
        }

        $document = $this->documentRepository->findByDossierAndExternalId($dossier, $data->getDocumentExternalId());
        if (! $document instanceof Document) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No document found for this dossier and organisation'));
        }

        $this->uploadDocumentHandler->handle($dossier, $document, $data->getContent());
    }
}
