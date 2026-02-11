<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision\Uploads\MainDocument;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\Publication\UploadProcessor;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\MainDocument\WooDecisionMainDocument;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Domain\Publication\MainDocument\MainDocumentRepository;
use Shared\Service\Uploader\UploadGroupId;
use Symfony\Component\Validator\ConstraintViolationList;
use Webmozart\Assert\Assert;

readonly class WooDecisionUploadMainDocumentProcessor implements ProcessorInterface
{
    public function __construct(
        private MainDocumentRepository $mainDocumentRepository,
        private OrganisationRepository $organisationRepository,
        private UploadProcessor $uploadProcessor,
        private WooDecisionRepository $wooDecisionRepository,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        if (! $data instanceof WooDecisionUploadMainDocument) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Invalid main document request'));
        }

        $organisation = $this->organisationRepository->find($data->organisationId);
        Assert::isInstanceOf($organisation, Organisation::class);

        $wooDecision = $this->wooDecisionRepository->find($data->dossierId);
        Assert::isInstanceOf($wooDecision, WooDecision::class);

        if (! $organisation->getId()->equals($wooDecision->getOrganisation()->getId())) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No dossier found for this organisation'));
        }

        $mainDocument = $this->mainDocumentRepository->find($data->uploadId);
        if (! $mainDocument instanceof WooDecisionMainDocument) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No main document found'));
        }

        if (! $wooDecision->getId()->equals($mainDocument->getDossier()->getId())) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No main document found for this dossier'));
        }

        if ($data->content === '') {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No file content provided'));
        }

        $fileName = $mainDocument->getFileInfo()->getName();
        Assert::notNull($fileName);

        $this->uploadProcessor->process($wooDecision->getId(), UploadGroupId::MAIN_DOCUMENTS, $data->content, $fileName);

        return null;
    }
}
