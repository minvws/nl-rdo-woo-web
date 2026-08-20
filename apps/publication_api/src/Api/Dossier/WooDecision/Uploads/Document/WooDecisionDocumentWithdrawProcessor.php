<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision\Uploads\Document;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\Exception\ValidationException;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\Organisation\OrganisationResolverInterface;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\Document;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentDispatcher;
use Shared\Domain\Publication\Dossier\Type\WooDecision\Document\DocumentRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecision;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\DocumentWorkflow\DocumentWorkflowStatus;
use Symfony\Component\Validator\ConstraintViolationList;
use Webmozart\Assert\Assert;

final readonly class WooDecisionDocumentWithdrawProcessor implements ProcessorInterface
{
    public function __construct(
        private OrganisationResolverInterface $organisationResolver,
        private WooDecisionRepository $wooDecisionRepository,
        private DocumentRepository $documentRepository,
        private DocumentDispatcher $documentDispatcher,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        unset($context);

        if (! $operation instanceof Put) {
            return null;
        }

        Assert::isInstanceOf($data, WooDecisionDocumentWithdrawRequestDto::class);
        Assert::string($uriVariables['dossierExternalId']);
        Assert::string($uriVariables['organisationId']);
        Assert::string($uriVariables['documentExternalId']);

        $dossierExternalId = ExternalIdFactory::create($uriVariables['dossierExternalId']);
        $documentExternalId = ExternalIdFactory::create($uriVariables['documentExternalId']);
        $organisation = $this->organisationResolver->resolve($uriVariables);

        $dossier = $this->wooDecisionRepository->findByOrganisationAndExternalId(
            $organisation,
            $dossierExternalId,
        );
        if (! $dossier instanceof WooDecision) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No dossier found for this organisation'));
        }

        $document = $this->documentRepository->findByDossierAndExternalId(
            $dossier,
            $documentExternalId,
        );

        if (! $document instanceof Document) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('No document found for this dossier'));
        }

        $status = new DocumentWorkflowStatus($document);
        if (! $status->canWithdraw()) {
            throw new ValidationException(ConstraintViolationList::createFromMessage('Document withdraw is not allowed in current state'));
        }

        $this->documentDispatcher->dispatchWithdrawDocumentCommand(
            $dossier,
            $document,
            $data->reason,
            $data->explanation,
        );

        return null;
    }
}
