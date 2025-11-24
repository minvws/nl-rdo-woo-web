<?php

declare(strict_types=1);

namespace Shared\Api\Publication\V1\Prefix;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\DocumentPrefixRepository;
use Shared\Service\ApiPlatformService;
use Symfony\Component\Uid\Uuid;
use Webmozart\Assert\Assert;

final readonly class PrefixProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private DocumentPrefixRepository $documentPrefixRepository,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|PrefixDto|null
    {
        $organisation = $this->organisationRepository->find($uriVariables['organisationId']);
        if ($organisation === null) {
            return null;
        }

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        $documentPrefixId = $uriVariables['prefixId'];
        Assert::isInstanceOf($documentPrefixId, Uuid::class);

        return $this->provideSingle($organisation, $documentPrefixId);
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

        return new ArrayPaginator(PrefixMapper::fromEntities($documentPrefixes), 0, count($documentPrefixes));
    }

    private function provideSingle(Organisation $organisation, Uuid $documentPrefixId): ?PrefixDto
    {
        $documentPrefix = $this->documentPrefixRepository->findByOrganisationAndId($organisation, $documentPrefixId);
        if ($documentPrefix === null) {
            return null;
        }

        return PrefixMapper::fromEntity($documentPrefix);
    }
}
