<?php

declare(strict_types=1);

namespace PublicationApi\Api\Dossier;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use PublicationApi\Api\ExternalIdFactory;
use PublicationApi\Api\Organisation\OrganisationResolver;
use PublicationApi\Domain\OpenApi\Exception\ValidationException;
use Shared\Domain\Publication\Dossier\AbstractDossier;
use Webmozart\Assert\Assert;

/**
 * @template TDossier of AbstractDossier
 * @template TDossierRequestDto of DossierRequestDtoInterface
 * @template TDossierResponseDto of DossierResponseDtoInterface
 *
 * @implements ProcessorInterface<TDossierRequestDto,TDossierResponseDto>
 */
abstract readonly class DossierProcessor implements ProcessorInterface
{
    /**
     * @param DossierStrategy<TDossier,TDossierRequestDto> $dossierStrategy
     * @param DossierResponseMapper<TDossier,TDossierResponseDto> $dossierResponseMapper
     */
    public function __construct(
        private DossierRequestHandler $dossierRequestHandler,
        private OrganisationResolver $organisationResolver,
        private DossierStrategy $dossierStrategy,
        private DossierResponseMapper $dossierResponseMapper,
    ) {
    }

    /**
     * @param array<array-key, mixed> $uriVariables
     *
     * @throws ValidationException
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): DossierResponseDtoInterface
    {
        unset($context);

        Assert::isInstanceOf($operation, Put::class);
        Assert::isInstanceOf($data, DossierRequestDtoInterface::class);
        Assert::string($uriVariables['dossierExternalId']);

        $dossier = $this->dossierRequestHandler->handle(
            $data,
            $this->organisationResolver->resolve($uriVariables),
            ExternalIdFactory::create($uriVariables['dossierExternalId']),
            $this->dossierStrategy,
        );

        return $this->dossierResponseMapper->fromEntity($dossier);
    }
}
