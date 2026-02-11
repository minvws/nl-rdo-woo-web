<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\Disposition;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionRepository;
use Shared\Service\ApiPlatformService;

use function count;

final readonly class DispositionProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private DispositionRepository $dispositionRepository,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|DispositionDto|null
    {
        $organisation = $this->organisationRepository->find($uriVariables['organisationId']);
        if ($organisation === null) {
            return null;
        }

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        return $this->provideSingle($organisation, $uriVariables['dispositionExternalId']);
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function provideCollection(Organisation $organisation, array $context): ArrayPaginator
    {
        $dispositions = $this->dispositionRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        return new ArrayPaginator(DispositionMapper::fromEntities($dispositions), 0, count($dispositions));
    }

    private function provideSingle(Organisation $organisation, string $dispositionExternalId): ?DispositionDto
    {
        $disposition = $this->dispositionRepository->findByOrganisationAndExternalId($organisation, $dispositionExternalId);
        if ($disposition === null) {
            return null;
        }

        return DispositionMapper::fromEntity($disposition);
    }
}
