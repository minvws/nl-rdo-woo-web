<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantRepository;
use Shared\Service\ApiPlatformService;
use Shared\ValueObject\ExternalId;

use function count;

final readonly class CovenantProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private CovenantRepository $covenantRepository,
        private CovenantMapper $covenantMapper,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|CovenantResponseDto
    {
        $organisation = $this->organisationResolver->resolve($uriVariables);

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        return $this->provideSingle($organisation, ExternalIdFactory::create($uriVariables['dossierExternalId']));
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

        return new ArrayPaginator($this->covenantMapper->fromEntities($covenants), 0, count($covenants));
    }

    private function provideSingle(Organisation $organisation, ExternalId $covenantExternalId): CovenantResponseDto
    {
        $covenant = $this->covenantRepository->findByOrganisationAndExternalId($organisation, $covenantExternalId);
        if ($covenant === null) {
            throw EntityNotFoundException::for('Covenant', $covenantExternalId);
        }

        return $this->covenantMapper->fromEntity($covenant);
    }
}
