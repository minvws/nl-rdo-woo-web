<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Api\Pagination\CursorPageFactory;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\HasId;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\ApiPlatformService;
use Shared\ValueObject\ExternalId;

final readonly class WooDecisionProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private WooDecisionRepository $wooDecisionRepository,
        private WooDecisionMapper $wooDecisionMapper,
        private CursorPageFactory $cursorPageFactory,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key, string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CursorPage|WooDecisionResponseDto
    {
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
        $wooDecisions = $this->wooDecisionRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        $mappedDtos = $this->wooDecisionMapper->fromEntities($wooDecisions);

        /** @var list<HasId> $wooDecisions */
        return $this->cursorPageFactory->create(
            $wooDecisions,
            $mappedDtos,
            $this->itemsPerPage,
            $operation,
            $uriVariables,
        );
    }

    private function provideSingle(Organisation $organisation, ExternalId $dossierExternalId): WooDecisionResponseDto
    {
        $wooDecision = $this->wooDecisionRepository->findByOrganisationAndExternalId($organisation, $dossierExternalId);
        if ($wooDecision === null) {
            throw EntityNotFoundException::for('WooDecision', $dossierExternalId);
        }

        return $this->wooDecisionMapper->fromEntity($wooDecision);
    }
}
