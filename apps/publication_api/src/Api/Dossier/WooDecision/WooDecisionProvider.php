<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\WooDecision;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\ApiPlatformService;
use Shared\ValueObject\ExternalId;

use function count;

final readonly class WooDecisionProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private WooDecisionMapper $wooDecisionMapper,
        private WooDecisionRepository $wooDecisionRepository,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|WooDecisionResponseDto
    {
        $organisation = $this->organisationResolver->resolve($uriVariables);

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        return $this->provideSingle($organisation, ExternalId::create($uriVariables['dossierExternalId']));
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function provideCollection(Organisation $organisation, array $context): ArrayPaginator
    {
        $wooDecisions = $this->wooDecisionRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        return new ArrayPaginator($this->wooDecisionMapper->fromEntities($wooDecisions), 0, count($wooDecisions));
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
