<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Decorator;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use Shared\Domain\Publication\Attachment\Enum\AttachmentType;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_map;
use function is_array;
use function is_string;

/**
 * @phpstan-type SchemaArray array{properties?: array<string, mixed>}
 */
#[AsDecorator(decorates: 'api_platform.openapi.factory')]
final readonly class AttachmentTypeDescriptionDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $components = $openApi->getComponents();
        /** @var ArrayObject<string, mixed>|null $schemas */
        $schemas = $components->getSchemas();

        if ($schemas === null) {
            return $openApi;
        }

        $attachmentProps = $this->findAttachmentTypeProperties($schemas);
        if ($attachmentProps === []) {
            return $openApi;
        }

        $enumDescriptions = $this->buildEnumDescriptions();

        foreach ($attachmentProps as [$schemaName, $propName]) {
            $schema = $schemas[$schemaName];
            if (! $schema instanceof ArrayObject) {
                continue;
            }

            $properties = $schema['properties'];
            if ($properties === null || (! is_array($properties) && ! $properties instanceof ArrayObject)) {
                continue;
            }

            $property = $properties[$propName];
            if ($property === null || (! is_array($property) && ! $property instanceof ArrayObject)) {
                continue;
            }

            if (! isset($property['enum'])) {
                continue;
            }

            /** @var string[] $enumValues */
            $enumValues = $property['enum'];

            $varnames = array_map(
                fn (string $value) => $enumDescriptions[$value] ?? $value,
                $enumValues
            );

            $property['x-enum-varnames'] = $varnames;
        }

        return $openApi;
    }

    /**
     * @param ArrayObject<string, mixed> $schemas
     *
     * @return array<array{0: string, 1: string}>
     */
    private function findAttachmentTypeProperties(ArrayObject $schemas): array
    {
        $attachmentProps = [];

        foreach ($schemas as $schemaName => $schema) {
            if (! is_string($schemaName)) {
                continue;
            }

            if (! $this->isValidSchema($schema)) {
                continue;
            }

            if (! $schema instanceof ArrayObject) {
                continue;
            }

            $properties = $schema['properties'] ?? null;
            if ($properties === null || (! is_array($properties) && ! $properties instanceof ArrayObject)) {
                continue;
            }

            foreach ($properties as $propName => $property) {
                if (! is_array($property) && ! $property instanceof ArrayObject) {
                    continue;
                }

                if (isset($property['class']) && $property['class'] === 'AttachmentType') {
                    $attachmentProps[] = [$schemaName, $propName];
                    unset($property['class']);
                }
            }
        }

        return $attachmentProps;
    }

    private function isValidSchema(mixed $schema): bool
    {
        return (is_array($schema) || $schema instanceof ArrayObject) && isset($schema['properties']);
    }

    /** @return array<string, string> */
    private function buildEnumDescriptions(): array
    {
        $descriptions = [];
        foreach (AttachmentType::cases() as $case) {
            $descriptions[$case->value] = $case->trans($this->translator);
        }

        return $descriptions;
    }
}
