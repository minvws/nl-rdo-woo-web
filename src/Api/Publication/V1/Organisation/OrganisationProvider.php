<?php

declare(strict_types=1);

namespace App\Api\Publication\V1\Organisation;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use App\Domain\Organisation\OrganisationRepository;
use App\Service\ApiPlatformService;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

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
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|OrganisationDto|null
    {
        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($context);
        }

        $organisationId = $uriVariables['organisationId'];
        Assert::isInstanceOf($organisationId, Uuid::class);

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

    private function provideSingle(Uuid $organisationId): ?OrganisationDto
    {
        $organisation = $this->organisationRepository->find($organisationId);
        if ($organisation === null) {
            return null;
        }

        return OrganisationMapper::fromEntity($organisation);
    }
}
