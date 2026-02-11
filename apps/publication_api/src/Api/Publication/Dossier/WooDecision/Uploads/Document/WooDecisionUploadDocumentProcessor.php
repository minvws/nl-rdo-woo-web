<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\Document;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Publication\UploadProcessor;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Judgement;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\Validator\ConstraintViolationList;
use Webmozart\Assert\Assert;

readonly class WooDecisionUploadDocumentProcessor implements ProcessorInterface
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private OrganisationRepository $organisationRepository,
        private UploadProcessor $uploadProcessor,
        private WooDecisionRepository $wooDecisionRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        if (! $data instanceof WooDecisionUploadDocument) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Invalid document request'));
        }

        if ($data->content === '') {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No file content provided'));
        }

        $organisation = $this->organisationRepository->find($data->organisationId);
        Assert::isInstanceOf($organisation, Organisation::class);

        $wooDecision = $this->wooDecisionRepository->findByOrganisationAndExternalId($organisation, $data->dossierExternalId->__toString());
        if (! $wooDecision instanceof WooDecision) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No dossier found for this organisation'));
        }

        $document = $this->documentRepository->findByDossierAndExternalId($wooDecision, $data->documentExternalId);
        if (! $document instanceof Document) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No document found'));
        }

        if ($document->getJudgement() === Judgement::NOT_PUBLIC) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Document is not public'));
        }

        if ($document->isSuspended()) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Document is suspended'));
        }

        if ($document->isWithdrawn()) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Document is withdrawn'));
        }

        $fileName = $document->getFileInfo()->getName();
        Assert::notNull($fileName);

        $this->uploadProcessor->process($wooDecision->getId(), UploadGroupId::WOO_DECISION_DOCUMENTS, $data->content, $fileName);

        return null;
    }
}
