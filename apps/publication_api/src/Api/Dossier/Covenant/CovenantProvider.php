<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Covenant;

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
use Shared\Domain\Publication\Dossier\Type\Covenant\CovenantRepository;
use Shared\Service\ApiPlatformService;
use Shared\ValueObject\ExternalId;

final readonly class CovenantProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private CovenantRepository $covenantRepository,
        private CovenantMapper $covenantMapper,
        private CursorPageFactory $cursorPageFactory,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key, string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CursorPage|CovenantResponseDto
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
        $covenants = $this->covenantRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        $mappedDtos = $this->covenantMapper->fromEntities($covenants);

        /** @var list<HasId> $covenants */
        return $this->cursorPageFactory->create(
            $covenants,
            $mappedDtos,
            $this->itemsPerPage,
            $operation,
            $uriVariables,
        );
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
