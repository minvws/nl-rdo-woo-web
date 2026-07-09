<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

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
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceRepository;
use Shared\Service\ApiPlatformService;
use Shared\ValueObject\ExternalId;

final readonly class AdviceProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private AdviceRepository $adviceRepository,
        private AdviceMapper $adviceMapper,
        private CursorPageFactory $cursorPageFactory,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key, string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CursorPage|AdviceResponseDto
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
        $advices = $this->adviceRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        $mappedDtos = $this->adviceMapper->fromEntities($advices);

        /** @var list<HasId> $advices */
        return $this->cursorPageFactory->create(
            $advices,
            $mappedDtos,
            $this->itemsPerPage,
            $operation,
            $uriVariables,
        );
    }

    private function provideSingle(Organisation $organisation, ExternalId $dossierExternalId): AdviceResponseDto
    {
        $advice = $this->adviceRepository->findByOrganisationAndExternalId($organisation, $dossierExternalId);
        if ($advice === null) {
            throw EntityNotFoundException::for('Advice', $dossierExternalId);
        }

        return $this->adviceMapper->fromEntity($advice);
    }
}
