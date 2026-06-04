<?php

declare(strict_types=1);

namespace PublicationApi\Api\Organisation;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Service\ApiPlatformService;
use Symfony\Component\Uid\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

use function count;

final readonly class OrganisationProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|OrganisationResponseDto
    {
        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($context);
        }

        try {
            $organisationId = Uuid::fromString($uriVariables['organisationId']);
        } catch (InvalidArgumentException) {
            throw EntityNotFoundException::for('Organisation', $uriVariables['organisationId']);
        }

        return $this->provideSingle($organisationId);
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function provideCollection(array $context): ArrayPaginator
    {
        $organisations = $this->organisationRepository->getPaginated(
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        return new ArrayPaginator(OrganisationMapper::fromEntities($organisations), 0, count($organisations));
    }

    private function provideSingle(Uuid $organisationId): OrganisationResponseDto
    {
        $organisation = $this->organisationRepository->find($organisationId);
        if ($organisation === null) {
            throw EntityNotFoundException::for('Organisation', $organisationId);
        }

        return OrganisationMapper::fromEntity($organisation);
    }
}
