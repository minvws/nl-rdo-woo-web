<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\ArrayPaginator;
use ApiPlatform\State\ProviderInterface;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\Exception\EntityNotFoundException;
use Shared\Domain\Organisation\Organisation;
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceRepository;
use Shared\Service\ApiPlatformService;
use Shared\ValueObject\ExternalId;

use function count;

final readonly class RequestForAdviceProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private RequestForAdviceRepository $requestForAdviceRepository,
        private RequestForAdviceMapper $requestForAdviceMapper,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key,string> $uriVariables
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): ArrayPaginator|RequestForAdviceResponseDto {
        $organisation = $this->organisationResolver->resolve($uriVariables);

        if ($operation instanceof CollectionOperationInterface) {
            return $this->provideCollection($organisation, $context);
        }

        return $this->provideSingle($organisation, ExternalId::create($uriVariables['dossierExternalId']));
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

        return new ArrayPaginator($this->requestForAdviceMapper->fromEntities($requestForAdvices), 0, count($requestForAdvices));
    }

    private function provideSingle(Organisation $organisation, ExternalId $requestForAdviceExternalId): RequestForAdviceResponseDto
    {
        $requestForAdvice = $this->requestForAdviceRepository->findByOrganisationAndExternalId($organisation, $requestForAdviceExternalId);
        if ($requestForAdvice === null) {
            throw EntityNotFoundException::for('RequestForAdvice', $requestForAdviceExternalId);
        }

        return $this->requestForAdviceMapper->fromEntity($requestForAdvice);
    }
}
