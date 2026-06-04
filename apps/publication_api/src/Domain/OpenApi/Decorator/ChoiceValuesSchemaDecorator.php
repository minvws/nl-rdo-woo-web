<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Decorator;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Choice;
use Webmozart\Assert\Assert;

use function array_key_exists;
use function array_map;
use function array_unique;
use function array_values;
use function get_declared_classes;
use function implode;
use function is_array;
use function is_string;
use function sprintf;

use const PHP_EOL;

#[AsDecorator(decorates: 'api_platform.openapi.factory')]
final readonly class ChoiceValuesSchemaDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @throws ReflectionException
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $schemas = $openApi->getComponents()->getSchemas();
        if ($schemas === null) {
            return $openApi;
        }

        $this->applyAllowedValuesDescriptions($schemas);

        return $openApi;
    }

    /**
     * @param ArrayObject<string, mixed> $schemas
     *
     * @throws ReflectionException
     */
    private function applyAllowedValuesDescriptions(ArrayObject $schemas): void
    {
        $valueMap = $this->buildValueMap();

        foreach ($schemas as $schemaName => $schema) {
            if (! is_string($schemaName) || ! $schema instanceof ArrayObject) {
                continue;
            }

            if (! array_key_exists($schemaName, $valueMap)) {
                continue;
            }

            $this->applyAllowedValuesDescriptionsToSchema($schema, $valueMap[$schemaName]);
        }
    }

    /**
     * @param ArrayObject<string, mixed> $schema
     * @param array<string, array<array-key, string>> $propertyAllowedValues
     */
    private function applyAllowedValuesDescriptionsToSchema(ArrayObject $schema, array $propertyAllowedValues): void
    {
        $properties = $schema->offsetGet('properties');
        if (! is_array($properties)) {
            return;
        }

        foreach ($propertyAllowedValues as $propertyName => $allowedValues) {
            if (! array_key_exists($propertyName, $properties)) {
                continue;
            }

            $properties[$propertyName] = $this->buildArrayPropertySchema($allowedValues);
        }

        $schema->offsetSet('properties', $properties);
    }

    /**
     * @param array<array-key, string> $allowedValues
     *
     * @return array{type: string, description: string, items: array{type: string}}
     */
    private function buildArrayPropertySchema(array $allowedValues): array
    {
        return [
            'type' => 'array',
            'description' => $this->buildAllowedValuesDescription($allowedValues),
            'items' => ['type' => 'string'],
        ];
    }

    /**
     * @param array<array-key, string> $allowedValues
     */
    private function buildAllowedValuesDescription(array $allowedValues): string
    {
        $bulletPoints = implode(PHP_EOL, array_map(
            static function (string $allowedValue): string {
                return sprintf('- `%s`', $allowedValue);
            },
            $allowedValues,
        ));

        return sprintf('Allowed values:%s%s%s', PHP_EOL, PHP_EOL, $bulletPoints);
    }

    /**
     * @return array<string, array<string, array<array-key, string>>>
     *
     * @throws ReflectionException
     */
    private function buildValueMap(): array
    {
        $valueMap = [];

        foreach (get_declared_classes() as $className) {
            $classAllowedValues = $this->extractAllowedValuesFromClass($className);
            if ($classAllowedValues === []) {
                continue;
            }

            $shortClassName = new ReflectionClass($className)->getShortName();
            $valueMap[$shortClassName] = $classAllowedValues;
        }

        return $valueMap;
    }

    /**
     * @return array<string, array<array-key, string>>
     *
     * @throws ReflectionException
     */
    private function extractAllowedValuesFromClass(string $className): array
    {
        Assert::classExists($className);
        $reflection = new ReflectionClass($className);

        $classAllowedValues = [];

        foreach ($reflection->getProperties() as $property) {
            $propertyAllowedValues = $this->extractAllowedValuesFromProperty($property);
            if ($propertyAllowedValues === []) {
                continue;
            }

            $classAllowedValues[$property->getName()] = $propertyAllowedValues;
        }

        return $classAllowedValues;
    }

    /**
     * @return list<string>
     */
    private function extractAllowedValuesFromProperty(ReflectionProperty $property): array
    {
        $allAttributes = $property->getAttributes(All::class);

        foreach ($allAttributes as $allAttribute) {
            $allConstraint = $allAttribute->newInstance();
            $nestedConstraints = is_array($allConstraint->constraints)
                ? $allConstraint->constraints
                : [$allConstraint->constraints];

            foreach ($nestedConstraints as $nestedConstraint) {
                if (! $nestedConstraint instanceof Choice) {
                    continue;
                }

                $choices = $nestedConstraint->choices;
                if ($choices === null || $choices === []) {
                    continue;
                }
                Assert::allString($choices);

                return array_values(array_unique($choices));
            }
        }

        return [];
    }
}
