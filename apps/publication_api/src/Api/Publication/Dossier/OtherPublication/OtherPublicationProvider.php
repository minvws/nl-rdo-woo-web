<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\OtherPublication;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use Shared\Service\ApiPlatformService;

use function count;

final readonly class OtherPublicationProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private OtherPublicationRepository $otherPublicationRepository,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|OtherPublicationDto|null
    {
        $organisation = $this->organisationRepository->find($uriVariables['organisationId']);
        if ($organisation === null) {
            return null;
        }

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        return $this->provideSingle($organisation, $uriVariables['otherPublicationExternalId']);
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function provideCollection(Organisation $organisation, array $context): ArrayPaginator
    {
        $otherPublications = $this->otherPublicationRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        return new ArrayPaginator(OtherPublicationMapper::fromEntities($otherPublications), 0, count($otherPublications));
    }

    private function provideSingle(Organisation $organisation, string $otherPublicationExternalId): ?OtherPublicationDto
    {
        $otherPublication = $this->otherPublicationRepository->findByOrganisationAndExternalId($organisation, $otherPublicationExternalId);
        if ($otherPublication === null) {
            return null;
        }

        return OtherPublicationMapper::fromEntity($otherPublication);
    }
}
