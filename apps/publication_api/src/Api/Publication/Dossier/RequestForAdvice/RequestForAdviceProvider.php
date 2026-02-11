<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication\Dossier\RequestForAdvice;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Organisation\OrganisationRepository;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceRepository;
use Shared\Service\ApiPlatformService;

use function count;

final readonly class RequestForAdviceProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationRepository $organisationRepository,
        private RequestForAdviceRepository $requestForAdviceRepository,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ArrayPaginator|RequestForAdviceDto|null
    {
        $organisation = $this->organisationRepository->find($uriVariables['organisationId']);
        if ($organisation === null) {
            return null;
        }

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        return $this->provideSingle($organisation, $uriVariables['requestForAdviceExternalId']);
    }

    /**
     * @param array<array-key,mixed> $context
     */
    private function provideCollection(Organisation $organisation, array $context): ArrayPaginator
    {
        $requestForAdvices = $this->requestForAdviceRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        return new ArrayPaginator(RequestForAdviceMapper::fromEntities($requestForAdvices), 0, count($requestForAdvices));
    }

    private function provideSingle(Organisation $organisation, string $requestForAdviceExternalId): ?RequestForAdviceDto
    {
        $requestForAdvice = $this->requestForAdviceRepository->findByOrganisationAndExternalId($organisation, $requestForAdviceExternalId);
        if ($requestForAdvice === null) {
            return null;
        }

        return RequestForAdviceMapper::fromEntity($requestForAdvice);
    }
}
