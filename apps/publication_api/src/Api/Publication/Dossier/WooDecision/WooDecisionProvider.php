<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\WooDecision;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\Type\WooDecision\WooDecisionRepository;
use Shared\Service\ApiPlatformService;

use function count;

final readonly class WooDecisionProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private WooDecisionRepository $wooDecisionRepository,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|WooDecisionDto|null
    {
        $organisation = $this->organisationRepository->find($uriVariables['organisationId']);
        if ($organisation === null) {
            return null;
        }

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        return $this->provideSingle($organisation, $uriVariables['wooDecisionExternalId']);
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

        return new ArrayPaginator(WooDecisionMapper::fromEntities($wooDecisions), 0, count($wooDecisions));
    }

    private function provideSingle(Organisation $organisation, string $wooDecisionExternalId): ?WooDecisionDto
    {
        $wooDecision = $this->wooDecisionRepository->findByOrganisationAndExternalId($organisation, $wooDecisionExternalId);
        if ($wooDecision === null) {
            return null;
        }

        return WooDecisionMapper::fromEntity($wooDecision);
    }
}
