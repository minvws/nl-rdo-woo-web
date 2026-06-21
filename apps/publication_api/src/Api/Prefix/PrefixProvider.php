<?php

declare(strict_types=1);

namespace PublicationApi\Api\Prefix;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DocumentPrefixRepository;
use Shared\Service\ApiPlatformService;
use Symfony\Component\Uid\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

use function count;

final readonly class PrefixProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private DocumentPrefixRepository $documentPrefixRepository,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|PrefixDetailResponseDto
    {
        $organisation = $this->organisationResolver->resolve($uriVariables);

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        try {
            $prefixId = Uuid::fromString($uriVariables['prefixId']);
        } catch (InvalidArgumentException) {
            throw EntityNotFoundException::for('Prefix', $uriVariables['prefixId']);
        }

        return $this->provideSingle($organisation, $prefixId);
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function provideCollection(Organisation $organisation, array $context): ArrayPaginator
    {
        $documentPrefixes = $this->documentPrefixRepository->getByOrganisation(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        return new ArrayPaginator(PrefixMapper::fromEntitiesWithDetail($documentPrefixes), 0, count($documentPrefixes));
    }

    private function provideSingle(Organisation $organisation, Uuid $documentPrefixId): PrefixDetailResponseDto
    {
        $documentPrefix = $this->documentPrefixRepository->findByOrganisationAndId($organisation, $documentPrefixId);
        if ($documentPrefix === null) {
            throw EntityNotFoundException::for('Prefix', $documentPrefixId);
        }

        return PrefixMapper::fromEntityWithDetail($documentPrefix);
    }
}
