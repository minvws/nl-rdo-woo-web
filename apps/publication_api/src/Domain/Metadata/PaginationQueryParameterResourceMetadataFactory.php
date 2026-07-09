<?php

declare(strict_types=1);

namespace PublicationApi\Domain\Metadata;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Injects a cursor-pagination QueryParameter with validation constraints into every
 * GetCollection operation that has paginationViaCursor configured.
 */
#[AsDecorator(
    decorates: 'api_platform.metadata.resource.metadata_collection_factory',
    priority: 900,
)]
final readonly class PaginationQueryParameterResourceMetadataFactory implements ResourceMetadataCollectionFactoryInterface
{
    public function __construct(
        private ResourceMetadataCollectionFactoryInterface $inner,
    ) {
    }

    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $collection = $this->inner->create($resourceClass);

        $resources = [];
        foreach ($collection as $resource) {
            $operations = $resource->getOperations();
            if ($operations === null) {
                $resources[] = $resource;
                continue;
            }

            $updatedOperations = $operations;
            foreach ($operations as $operationName => $operation) {
                if (! $operation instanceof GetCollection || $operation->getPaginationViaCursor() === null) {
                    continue;
                }

                $parameters = $operation->getParameters() ?? new Parameters();
                if ($parameters->has('pagination')) {
                    continue;
                }

                $parameters = $parameters->add('pagination', $this->createPaginationParameter());
                $updatedOperations = $updatedOperations->add($operationName, $operation->withParameters($parameters));
            }

            $resources[] = $resource->withOperations($updatedOperations);
        }

        return new ResourceMetadataCollection($resourceClass, $resources);
    }

    private function createPaginationParameter(): QueryParameter
    {
        return new QueryParameter(
            key: 'pagination',
            openApi: new OpenApiParameter(
                name: 'pagination',
                in: 'query',
                description: 'The cursor to get the next page of results.',
                schema: [
                    'type' => 'object',
                    'properties' => ['cursor' => ['type' => 'string']],
                ],
                style: 'deepObject',
            ),
            constraints: [
                new Assert\When(
                    expression: 'value !== null',
                    constraints: [
                        new Assert\Type('array'),
                        new Assert\Collection(
                            fields: ['cursor' => [new Assert\Type('string'), new Assert\NotBlank()]],
                            allowExtraFields: false,
                            allowMissingFields: false,
                        ),
                    ],
                ),
            ],
        );
    }
}
