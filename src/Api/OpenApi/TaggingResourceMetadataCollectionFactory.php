<?php

declare(strict_types=1);

namespace App\Api\OpenApi;

use ApiPlatform\Metadata\Exception\ResourceClassNotFoundException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operations;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use ApiPlatform\OpenApi\Model\Operation as OpenApiOperation;

final readonly class TaggingResourceMetadataCollectionFactory implements ResourceMetadataCollectionFactoryInterface
{
    /** @var array<string,list<string>> */
    public const TAG_TO_NAMESPACE_PREFIX = [
        'App\\Api\\Admin\\' => ['admin'],
        'App\\Api\\Publication\\V1\\' => ['publication-v1'],
        'ApiPlatform\\State\\' => ['admin', 'publication-v1'],
    ];

    public function __construct(
        private ResourceMetadataCollectionFactoryInterface $decorated,
    ) {
    }

    /**
     * Creates a resource metadata.
     *
     * @param class-string<ResourceMetadataCollection> $resourceClass
     *
     * @throws ResourceClassNotFoundException
     */
    public function create(string $resourceClass): ResourceMetadataCollection
    {
        $collection = $this->decorated->create($resourceClass);

        $resourceTags = $this->getTagsBasedOnResource($resourceClass);

        foreach ($collection as $i => $resource) {
            /** @var ?Operations $operationsCollection */
            $operationsCollection = $resource->getOperations();

            if ($operationsCollection === null) {
                continue;
            }

            /** @var \Traversable<string,HttpOperation> $operations */
            $operations = $operationsCollection->getIterator();

            foreach ($operations as $name => $operation) {
                $openApiOperation = $operation->getOpenapi() ?? new OpenApiOperation();
                if (! $openApiOperation instanceof OpenApiOperation) {
                    continue;
                }

                $tags = array_values(array_unique([...($openApiOperation->getTags() ?? []), ...$resourceTags]));
                $openApiOperation = $openApiOperation->withTags($tags);

                /** @var Operations $operationsCollection */
                $operationsCollection->add($name, $operation->withOpenapi($openApiOperation));
            }

            $collection[$i] = $resource->withOperations($operationsCollection);
        }

        return $collection;
    }

    /**
     * @param class-string<ResourceMetadataCollection> $resourceClass
     *
     * @return list<string>
     */
    private function getTagsBasedOnResource(string $resourceClass): array
    {
        $allTags = [];

        foreach (self::TAG_TO_NAMESPACE_PREFIX as $namespacePrefix => $tags) {
            foreach ($tags as $tag) {
                if (! in_array($tag, $allTags, true) && str_starts_with($resourceClass, $namespacePrefix)) {
                    $allTags[] = $tag;
                }
            }
        }

        return $allTags;
    }
}
