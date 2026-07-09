<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier\RequestForAdvice;

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
use Shared\Domain\Publication\Dossier\Type\RequestForAdvice\RequestForAdviceRepository;
use Shared\Service\ApiPlatformService;
use Shared\ValueObject\ExternalId;

final readonly class RequestForAdviceProvider implements ProviderInterface
{
    public function __construct(
        private OrganisationResolver $organisationResolver,
        private RequestForAdviceRepository $requestForAdviceRepository,
        private RequestForAdviceMapper $requestForAdviceMapper,
        private CursorPageFactory $cursorPageFactory,
        private int $itemsPerPage,
    ) {
    }

    /**
     * @param array<array-key, string> $uriVariables
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): CursorPage|RequestForAdviceResponseDto {
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
        $requestForAdvices = $this->requestForAdviceRepository->getByOrganisationAndContainsExternalId(
            $organisation,
            $this->itemsPerPage,
            ApiPlatformService::getCursorFromContext($context),
        );

        $mappedDtos = $this->requestForAdviceMapper->fromEntities($requestForAdvices);

        /** @var list<HasId> $requestForAdvices */
        return $this->cursorPageFactory->create(
            $requestForAdvices,
            $mappedDtos,
            $this->itemsPerPage,
            $operation,
            $uriVariables,
        );
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
