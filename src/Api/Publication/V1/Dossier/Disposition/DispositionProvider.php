<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier\Disposition;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionRepository;
use Shared\Service\ApiPlatformService;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final readonly class DispositionProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private DispositionRepository $dispositionRepository,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,Uuid> $uriVariables
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

        $dispositionId = $uriVariables['dispositionId'];
        Assert::isInstanceOf($dispositionId, Uuid::class);

        return $this->provideSingle($organisation, $dispositionId);
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function provideCollection(Organisation $organisation, array $context): ArrayPaginator
    {
        $dispositions = $this->dispositionRepository->getByOrganisation(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        return new ArrayPaginator(DispositionMapper::fromEntities($dispositions), 0, count($dispositions));
    }

    private function provideSingle(Organisation $organisation, Uuid $dispositionId): ?DispositionDto
    {
        $disposition = $this->dispositionRepository->findByOrganisationAndId($organisation, $dispositionId);
        if ($disposition === null) {
            return null;
        }

        return DispositionMapper::fromEntity($disposition);
    }
}
