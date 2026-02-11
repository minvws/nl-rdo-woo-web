<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Covenant;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantRepository;
use Shared\Service\ApiPlatformService;

use function count;

final readonly class CovenantProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private CovenantRepository $covenantRepository,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|CovenantDto|null
    {
        $organisation = $this->organisationRepository->find($uriVariables['organisationId']);
        if ($organisation === null) {
            return null;
        }

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        return $this->provideSingle($organisation, $uriVariables['covenantExternalId']);
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function provideCollection(Organisation $organisation, array $context): ArrayPaginator
    {
        $covenants = $this->covenantRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        return new ArrayPaginator(CovenantMapper::fromEntities($covenants), 0, count($covenants));
    }

    private function provideSingle(Organisation $organisation, string $covenantExternalId): ?CovenantDto
    {
        $covenant = $this->covenantRepository->findByOrganisationAndExternalId($organisation, $covenantExternalId);
        if ($covenant === null) {
            return null;
        }

        return CovenantMapper::fromEntity($covenant);
    }
}
