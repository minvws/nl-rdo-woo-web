<?php

declare(strict_types=1);

namespace App\Api\Admin\Publication\Search;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Operation;
use Symfony\Component\Validator\Constraints as Assert;

#[GetCollection(
    uriTemplate: 'publication/search',
    parameters: [
        'q' => new QueryParameter(
            required: true,
            constraints: [
                new Assert\NotBlank(normalizer: 'trim'),
                new Assert\Length(min: 3, max: 255, normalizer: 'trim'),
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
)]
final readonly class SearchResultDto
{
    public function __construct(
        public ?string $id,
        public SearchResultType $type,
        public string $title,
        public string $link,
    ) {
    }
}
