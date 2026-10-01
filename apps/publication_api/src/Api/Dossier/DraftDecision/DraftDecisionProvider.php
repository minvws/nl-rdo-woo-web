<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\DraftDecision;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Api\Pagination\CursorPageFactory;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use PublicationApi\Domain\OpenApi\Exception\ValidationException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\DraftDecision\DraftDecisionRepository;
use Shared\Service\ApiPlatformService;
use Shared\ValueObject\ExternalId;

final readonly class DraftDecisionProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private DraftDecisionRepository $draftDecisionRepository,
        private DraftDecisionResponseMapper $draftDecisionResponseMapper,
        private CursorPageFactory $cursorPageFactory,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key, string> $uriVariables
     *
     * @throws ValidationException
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CursorPage|DraftDecisionResponseDto {
        $organisation = $this->organisationResolver->resolve($uriVariables);

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $operation, $uriVariables, $context);
        }

        return $this->provideSingle($organisation, ExternalIdFactory::create($uriVariables['dossierExternalId']));
    }

    /**
     * @param array<array-key,mixed> $context
     * @param array<array-key, string> $uriVariables
     */
    private function provideCollection(
        Organisation $organisation,
        Operation $operation,
        array $uriVariables,
        array $context,
    ): CursorPage {
        $draftDecisions = $this->draftDecisionRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        $mappedDtos = $this->draftDecisionResponseMapper->fromEntities($draftDecisions);

        return $this->cursorPageFactory->create(
            $draftDecisions,
            $mappedDtos,
            $this->itemsPerPage,
            $operation,
            $uriVariables,
        );
    }

    private function provideSingle(Organisation $organisation, ExternalId $draftDecisionExternalId): DraftDecisionResponseDto
    {
        $draftDecision = $this->draftDecisionRepository->findByOrganisationAndExternalId($organisation, $draftDecisionExternalId);
        if ($draftDecision === null) {
            throw EntityNotFoundException::for('DraftDecision', $draftDecisionExternalId);
        }

        return $this->draftDecisionResponseMapper->fromEntity($draftDecision);
    }
}
