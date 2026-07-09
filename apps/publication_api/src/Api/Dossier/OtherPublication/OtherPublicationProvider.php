<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\OtherPublication;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Api\Pagination\CursorPageFactory;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\HasId;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\OtherPublication\OtherPublicationRepository;
use Shared\Service\ApiPlatformService;
use Shared\ValueObject\ExternalId;

final readonly class OtherPublicationProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private OtherPublicationRepository $otherPublicationRepository,
        private OtherPublicationMapper $otherPublicationMapper,
        private CursorPageFactory $cursorPageFactory,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key, string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CursorPage|OtherPublicationResponseDto
    {
        $organisation = $this->organisationResolver->resolve($uriVariables);

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $operation, $uriVariables, $context);
        }

        return $this->provideSingle($organisation, ExternalIdFactory::create($uriVariables['dossierExternalId']));
    }

    /**
     * @param array<array-key,mixed> $context
     * @param array<array-key, string> $uriVariables
     */
    private function provideCollection(
        Organisation $organisation,
        Operation $operation,
        array $uriVariables,
        array $context,
    ): CursorPage {
        $otherPublications = $this->otherPublicationRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        $mappedDtos = $this->otherPublicationMapper->fromEntities($otherPublications);

        /** @var list<HasId> $otherPublications */
        return $this->cursorPageFactory->create(
            $otherPublications,
            $mappedDtos,
            $this->itemsPerPage,
            $operation,
            $uriVariables,
        );
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
