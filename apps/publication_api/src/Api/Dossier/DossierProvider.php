<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Api\Pagination\CursorPageFactory;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use PublicationApi\Domain\OpenApi\Exception\ValidationException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Shared\Domain\Publication\Dossier\Type\AbstractDossierRepository;
use Shared\Service\ApiPlatformService;
use Shared\ValueObject\ExternalId;

/**
 * @template TDossier of AbstractDossier
 * @template TDossierResponseDto of DossierResponseDtoInterface
 */
abstract readonly class DossierProvider implements ProviderInterface
{
    /**
     * @param AbstractDossierRepository<TDossier> $abstractDossierRepository
     * @param DossierResponseMapper<TDossier,TDossierResponseDto> $dossierResponseMapper
     */
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private AbstractDossierRepository $abstractDossierRepository,
        private DossierResponseMapper $dossierResponseMapper,
        private CursorPageFactory $cursorPageFactory,
        private int $itemsPerPage,
        private string $entityName,
    ) {
    }

    /**
     * @param array<array-key, string> $uriVariables
     *
     * @throws ValidationException
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CursorPage|DossierResponseDtoInterface
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
        $dossiers = $this->abstractDossierRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        $mappedDtos = $this->dossierResponseMapper->fromEntities($dossiers);

        return $this->cursorPageFactory->create(
            $dossiers,
            $mappedDtos,
            $this->itemsPerPage,
            $operation,
            $uriVariables,
        );
    }

    /**
     * @return TDossierResponseDto
     */
    private function provideSingle(Organisation $organisation, ExternalId $dossierExternalId): DossierResponseDtoInterface
    {
        $dossier = $this->abstractDossierRepository->findByOrganisationAndExternalId($organisation, $dossierExternalId);
        if (! $dossier instanceof AbstractDossier) {
            throw EntityNotFoundException::for($this->entityName, $dossierExternalId->toString());
        }

        return $this->dossierResponseMapper->fromEntity($dossier);
    }
}
