<?php

declare(strict_types=1);

namespace App\Api\Admin\Publication\Search;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use App\Api\Admin\Publication\Search\Parameter\DossierIdParameterProvider;
use App\Api\Admin\Publication\Search\Parameter\PublicationTypeFilterParameterProvider;
use App\Api\Admin\Publication\Search\Parameter\ResultTypeFilterParameterProvider;
use App\Api\Admin\Publication\Search\Parameter\SearchParameterProvider;
use App\Domain\Publication\Dossier\Type\DossierType;
use Symfony\Component\Validator\Constraints as Assert;

#[GetCollection(
    uriTemplate: 'publication/search',
    parameters: [
        'q' => new QueryParameter(
            provider: SearchParameterProvider::class,
            required: true,
            constraints: [
                new Assert\NotBlank(normalizer: 'trim'),
                new Assert\Length(min: 3, max: 255, normalizer: 'trim'),
            ],
        ),
        'dossierId' => new QueryParameter(
            provider: DossierIdParameterProvider::class,
            constraints: [
                new Assert\Uuid(),
            ],
        ),
        'filter[publicationType]' => new QueryParameter(
            provider: PublicationTypeFilterParameterProvider::class,
            constraints: [
                new Assert\Choice(callback: [DossierType::class, 'getAllValues']),
            ],
        ),
        'filter[resultType]' => new QueryParameter(
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
        tags: ['PublicationSearch']
    ),
    provider: SearchProvider::class,
    normalizationContext: [
        'skip_null_values' => false,
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
