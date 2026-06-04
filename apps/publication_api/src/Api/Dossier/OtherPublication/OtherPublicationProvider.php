<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use Shared\Service\ApiPlatformService;
use Shared\ValueObject\ExternalId;

use function count;

final readonly class OtherPublicationProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private OtherPublicationRepository $otherPublicationRepository,
        private OtherPublicationMapper $otherPublicationMapper,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): ArrayPaginator|OtherPublicationResponseDto {
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
        $otherPublications = $this->otherPublicationRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        return new ArrayPaginator($this->otherPublicationMapper->fromEntities($otherPublications), 0, count($otherPublications));
    }

    private function provideSingle(Organisation $organisation, ExternalId $otherPublicationExternalId): OtherPublicationResponseDto
    {
        $otherPublication = $this->otherPublicationRepository->findByOrganisationAndExternalId($organisation, $otherPublicationExternalId);
        if ($otherPublication === null) {
            throw EntityNotFoundException::for('OtherPublication', $otherPublicationExternalId);
        }

        return $this->otherPublicationMapper->fromEntity($otherPublication);
    }
}
