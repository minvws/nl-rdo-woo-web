<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Dossier\OtherPublication;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use Shared\Service\ApiPlatformService;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final readonly class OtherPublicationProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private OtherPublicationRepository $otherPublicationRepository,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,Uuid> $uriVariables
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

        $otherPublicationId = $uriVariables['otherPublicationId'];
        Assert::isInstanceOf($otherPublicationId, Uuid::class);

        return $this->provideSingle($organisation, $otherPublicationId);
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function provideCollection(Organisation $organisation, array $context): ArrayPaginator
    {
        $otherPublications = $this->otherPublicationRepository->getByOrganisation(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        return new ArrayPaginator(OtherPublicationMapper::fromEntities($otherPublications), 0, count($otherPublications));
    }

    private function provideSingle(Organisation $organisation, Uuid $otherPublicationId): ?OtherPublicationDto
    {
        $otherPublication = $this->otherPublicationRepository->findByOrganisationAndId($organisation, $otherPublicationId);
        if ($otherPublication === null) {
            return null;
        }

        return OtherPublicationMapper::fromEntity($otherPublication);
    }
}
