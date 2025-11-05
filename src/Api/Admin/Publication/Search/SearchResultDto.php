<?php

declare(strict_types=1);

namespace App\Api\Admin\Publication\Search;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Factory\OpenApiFactory;
use ApiPlatform\OpenApi\Model\Operation;
use App\Api\Admin\Publication\Search\Parameter\DossierIdParameterProvider;
use App\Api\Admin\Publication\Search\Parameter\PublicationTypeFilterParameterProvider;
use App\Api\Admin\Publication\Search\Parameter\ResultTypeFilterParameterProvider;
use App\Api\Admin\Publication\Search\Parameter\SearchParameterProvider;
use App\Domain\Publication\Dossier\Type\DossierType;
use Symfony\Component\TypeInfo\Type;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/balie/api/publication/search',
            parameters: [
                'q' => new QueryParameter(
                    nativeType: new Type\BuiltinType(TypeIdentifier::STRING),
                    provider: SearchParameterProvider::class,
                    required: true,
                    constraints: [
                        new Assert\NotBlank(normalizer: 'trim'),
                        new Assert\Length(min: 3, max: 255, normalizer: 'trim'),
                    ],
                ),
                'dossierId' => new QueryParameter(
                    nativeType: new Type\BuiltinType(TypeIdentifier::STRING),
                    provider: DossierIdParameterProvider::class,
                    constraints: [
                        new Assert\Uuid(),
                    ],
                ),
                'filter[publicationType]' => new QueryParameter(
                    nativeType: new Type\BuiltinType(TypeIdentifier::STRING),
                    provider: PublicationTypeFilterParameterProvider::class,
                    constraints: [
                        new Assert\Choice(callback: [DossierType::class, 'getAllValues']),
                    ],
                ),
                'filter[resultType]' => new QueryParameter(
                    nativeType: new Type\BuiltinType(TypeIdentifier::STRING),
                    provider: ResultTypeFilterParameterProvider::class,
                    constraints: [
                        new Assert\Choice(callback: [SearchResultType::class, 'getAllValues']),
                    ],
                ),
            ],
            paginationEnabled: false,
            security: "is_granted('AuthMatrix.dossier.read')",
            stateless: false,
            openapi: new Operation(
                tags: ['PublicationSearch'],
                extensionProperties: [
                    OpenApiFactory::API_PLATFORM_TAG => ['admin'],
                ],
            ),
            provider: SearchProvider::class,
            normalizationContext: [
                'skip_null_values' => false,
            ],
        ),
    ],
)]
final readonly class SearchResultDto
{
    public function __construct(
        public string $id,
        public SearchResultType $type,
        public string $title,
        public string $link,
        public ?string $number = null,
    ) {
    }
}
