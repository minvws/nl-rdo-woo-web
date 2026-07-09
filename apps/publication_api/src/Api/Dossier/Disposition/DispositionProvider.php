<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Disposition;

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
use Shared\Domain\Publication\Dossier\Type\Disposition\DispositionRepository;
use Shared\Service\ApiPlatformService;
use Shared\ValueObject\ExternalId;

final readonly class DispositionProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private DispositionRepository $dispositionRepository,
        private DispositionMapper $dispositionMapper,
        private CursorPageFactory $cursorPageFactory,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key, string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CursorPage|DispositionResponseDto
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
        $dispositions = $this->dispositionRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        $mappedDtos = $this->dispositionMapper->fromEntities($dispositions);

        /** @var list<HasId> $dispositions */
        return $this->cursorPageFactory->create(
            $dispositions,
            $mappedDtos,
            $this->itemsPerPage,
            $operation,
            $uriVariables,
        );
    }

    private function provideSingle(Organisation $organisation, ExternalId $dispositionExternalId): DispositionResponseDto
    {
        $disposition = $this->dispositionRepository->findByOrganisationAndExternalId($organisation, $dispositionExternalId);
        if ($disposition === null) {
            throw EntityNotFoundException::for('Disposition', $dispositionExternalId);
        }

        return $this->dispositionMapper->fromEntity($disposition);
    }
}
