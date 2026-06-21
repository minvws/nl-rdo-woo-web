<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\Advice;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\Advice\AdviceRepository;
use Shared\Service\ApiPlatformService;
use Shared\ValueObject\ExternalId;

use function count;

final readonly class AdviceProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private AdviceRepository $adviceRepository,
        private AdviceMapper $adviceMapper,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|AdviceResponseDto
    {
        $organisation = $this->organisationResolver->resolve($uriVariables);

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        return $this->provideSingle($organisation, ExternalIdFactory::create($uriVariables['dossierExternalId']));
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function provideCollection(Organisation $organisation, array $context): ArrayPaginator
    {
        $advices = $this->adviceRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        return new ArrayPaginator($this->adviceMapper->fromEntities($advices), 0, count($advices));
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
