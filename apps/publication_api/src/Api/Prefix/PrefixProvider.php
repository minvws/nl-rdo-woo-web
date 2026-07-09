<?php

declare(strict_types=1);

namespace PublicationApi\Api\Prefix;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Api\Pagination\CursorPage;
use PublicationApi\Api\Pagination\CursorPageFactory;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\HasId;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\DocumentPrefixRepository;
use Shared\Service\ApiPlatformService;
use Symfony\Component\Uid\Exception\InvalidArgumentException;
use Symfony\Component\Uid\Uuid;

final readonly class PrefixProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private DocumentPrefixRepository $documentPrefixRepository,
        private CursorPageFactory $cursorPageFactory,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key, string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): CursorPage|PrefixDetailResponseDto
    {
        $organisation = $this->organisationResolver->resolve($uriVariables);

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $operation, $uriVariables, $context);
        }

        try {
            $prefixId = Uuid::fromString((string) $uriVariables['prefixId']);
        } catch (InvalidArgumentException) {
            throw EntityNotFoundException::for('Prefix', $uriVariables['prefixId']);
        }

        return $this->provideSingle($organisation, $prefixId);
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
        $documentPrefixes = $this->documentPrefixRepository->getByOrganisation(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        $mappedDtos = PrefixMapper::fromEntitiesWithDetail($documentPrefixes);

        /** @var list<HasId> $documentPrefixes */
        /** @var list<PrefixDetailResponseDto> $mappedDtos */
        return $this->cursorPageFactory->create(
            $documentPrefixes,
            $mappedDtos,
            $this->itemsPerPage,
            $operation,
            $uriVariables,
        );
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
