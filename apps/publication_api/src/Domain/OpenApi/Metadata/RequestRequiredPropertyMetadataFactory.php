<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Metadata;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Property\Factory\PropertyMetadataFactoryInterface;
use ReflectionClass;
use ReflectionParameter;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Webmozart\Assert\Assert;

#[AsDecorator(decorates: 'api_platform.metadata.property.metadata_factory', priority: -20)]
final readonly class RequestRequiredPropertyMetadataFactory implements PropertyMetadataFactoryInterface
{
    public function __construct(private PropertyMetadataFactoryInterface $decorated)
    {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function create(string $resourceClass, string $property, array $options = []): ApiProperty
    {
        Assert::classExists($resourceClass);

        $propertyMetadata = $this->decorated->create($resourceClass, $property, $options);

        $constructor = new ReflectionClass($resourceClass)->getConstructor();
        if ($constructor === null) {
            return $propertyMetadata;
        }

        $parameter = $this->findConstructorParameter($constructor->getParameters(), $property);
        if ($parameter === null) {
            return $propertyMetadata;
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $propertyMetadata;
        }

        return $propertyMetadata->withRequired(true);
    }

    /**
     * @param list<ReflectionParameter> $parameters
     */
    private function findConstructorParameter(array $parameters, string $property): ?ReflectionParameter
    {
        foreach ($parameters as $parameter) {
            if ($parameter->getName() === $property) {
                return $parameter;
            }
        }

        return null;
    }
}
