<?php

declare(strict_types=1);

namespace App\Api\OpenApi\UsageDetector;

use ApiPlatform\OpenApi\OpenApi;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Webmozart\Assert\Assert;

/**
 * @SuppressWarnings("PHPMD.ExcessiveClassComplexity")
 */
class OpenApiComponentsUsageDetector
{
    public const COMPONENT_SECTIONS = [
        'schemas',
        'responses',
        'parameters',
        'examples',
        'requestBodies',
        'headers',
        'securitySchemes',
        'links',
        'callbacks',
        'pathItems',
        'extensionProperties',
    ];
    private const ROOT_KEYS = [
        'paths',
        'webhooks',
    ];
    private const COMPOSITION_KEYS = [
        'allOf',
        'anyOf',
        'oneOf',
    ];
    private const SINGLE_SCHEMA_KEYS = [
        'contains',
        'propertyNames',
        'unevaluatedProperties',
        'unevaluatedItems',
        'if',
        'then',
        'else',
        'not',
    ];
    private const MAP_SCHEMA_KEYS = [
        'patternProperties',
        'dependentSchemas',
        '$defs',
    ];

    /**
     * @var array<array-key,mixed>
     */
    private array $rootDocument = [];

    /**
     * Tracks already traversed JSON Pointer locations to avoid redundant work.
     * Keys are normalized JSON Pointers (e.g. "#/components/schemas/Foo/properties/bar").
     *
     * @var array<string,true>
     */
    private array $visited = [];

    public function __construct(private readonly NormalizerInterface $normalizer)
    {
    }

    public function detect(OpenApi $openApi): UsedComponents
    {
        $this->visited = [];
        $document = $this->normalizer->normalize($openApi, 'array');

        if (! is_array($document) || $document === []) {
            return UsedComponents::new();
        }

        $docComponents = $document['components'] ?? null;
        if (! is_array($docComponents) || $docComponents === []) {
            return UsedComponents::new();
        }

        return $this->doDetect($document, $docComponents);
    }

    /**
     * @param array<array-key,mixed> $document
     * @param array<array-key,mixed> $docComponents
     */
    private function doDetect(array $document, array $docComponents): UsedComponents
    {
        $this->rootDocument = $document;
        $initialRefs = UsedComponents::new();

        foreach (self::ROOT_KEYS as $rootKey) {
            if (isset($this->rootDocument[$rootKey]) && is_array($this->rootDocument[$rootKey])) {
                $this->collectRefs($this->rootDocument[$rootKey], $initialRefs, $this->addSuffixToPointer('#/', $rootKey));
            }
        }

        if (isset($this->rootDocument['security']) && is_array($this->rootDocument['security'])) {
            $this->collectSecurityRequirements($this->rootDocument['security'], $initialRefs);
        }

        if (! $initialRefs->hasItems()) {
            return $initialRefs;
        }

        return $this->expandComponentsRefs($initialRefs, $docComponents);
    }

    /**
     * @param array<array-key,mixed> $securityArray
     */
    private function collectSecurityRequirements(array $securityArray, UsedComponents $used): void
    {
        foreach ($securityArray as $requirement) {
            Assert::isArray($requirement, 'Security requirement must be an array');
            foreach ($requirement as $schemeName => $_scopes) {
                if (is_string($schemeName) && $schemeName !== '') {
                    $used->mark('securitySchemes', $schemeName);
                }
            }
        }
    }

    /**
     * @param array<array-key,mixed> $node
     */
    private function collectRefs(array $node, UsedComponents &$used, ?string $pointer = null): void
    {
        if ($pointer !== null) {
            if (isset($this->visited[$pointer])) {
                return;
            }

            $this->visited[$pointer] = true;
        }

        foreach ($node as $key => $value) {
            $childPointer = $this->addSuffixToPointer($pointer, $key);

            if ($this->dispatchNodeHandler($key, $value, $used, $childPointer)) {
                continue;
            }

            if (is_array($value)) {
                $this->collectRefs($value, $used, $childPointer);
            }
        }
    }

    /**
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    private function dispatchNodeHandler(mixed $key, mixed $value, UsedComponents &$used, ?string $pointer): bool
    {
        if (! is_string($key)) {
            return false;
        }

        if ($key === '$ref' && is_string($value)) {
            return $this->handleDirectRef($value, $used);
        }

        if (! is_array($value)) {
            return false;
        }

        switch (true) {
            case $key === 'security':
                return $this->handleSecurity($value, $used);

            case $key === 'discriminator':
                return $this->handleDiscriminator($value, $used);

            case $key === 'properties':
                return $this->handleProperties($value, $used, $pointer);

            case $key === 'items':
            case $key === 'additionalProperties':
            case in_array($key, self::SINGLE_SCHEMA_KEYS, true):
                return $this->handleSingleSchemaKeyword($value, $used, $pointer);

            case $key === 'prefixItems':
            case in_array($key, self::COMPOSITION_KEYS, true):
                return $this->handleListOfSchemas($value, $used, $pointer);

            case in_array($key, self::MAP_SCHEMA_KEYS, true):
                return $this->handleMapSchemaKeywords($value, $used, $pointer);

            default:
                return false;
        }
    }

    private function handleDirectRef(string $value, UsedComponents &$used): bool
    {
        if (preg_match('~^#/components/([^/]+)/([^/]+)(/.*)?$~', $value, $matches)) {
            $section = $matches[1];
            $name = $matches[2];
            $extra = $matches[3] ?? null;
            if (in_array($section, self::COMPONENT_SECTIONS, true)) {
                $used->mark($section, $name);
            }
            if ($extra !== null) {
                $resolved = $this->resolveJsonPointer($value);
                if (is_array($resolved)) {
                    $this->collectRefs($resolved, $used, $value);
                }
            }
        }

        return true;
    }

    /**
     * @param array<array-key,mixed> $value
     */
    private function handleSecurity(array $value, UsedComponents &$used): bool
    {
        $this->collectSecurityRequirements($value, $used);

        return true;
    }

    /**
     * @param array<array-key,mixed> $value
     */
    private function handleDiscriminator(array $value, UsedComponents &$used): bool
    {
        $mapping = $value['mapping'] ?? null;
        Assert::isArray($mapping, 'Discriminator mapping must be an array');

        foreach ($mapping as $mapVal) {
            if (is_string($mapVal) && str_starts_with($mapVal, '#/components/')) {
                $this->collectRefs(['$ref' => $mapVal], $used);
            }
        }

        return true;
    }

    /**
     * @param array<array-key,mixed> $value
     */
    private function handleProperties(array $value, UsedComponents &$used, ?string $pointer): bool
    {
        foreach ($value as $propertyName => $propSchema) {
            if (is_array($propSchema)) {
                $this->collectRefs($propSchema, $used, $this->addSuffixToPointer($pointer, $propertyName));
            }
        }

        return true;
    }

    /**
     * @param array<array-key,mixed> $value
     */
    private function handleListOfSchemas(array $value, UsedComponents &$used, ?string $pointer): bool
    {
        foreach ($value as $index => $schema) {
            if (is_array($schema)) {
                $this->collectRefs($schema, $used, $this->addSuffixToPointer($pointer, $index));
            }
        }

        return true;
    }

    /**
     * @param array<array-key,mixed> $value
     */
    private function handleSingleSchemaKeyword(array $value, UsedComponents &$used, ?string $pointer): bool
    {
        $this->collectRefs($value, $used, $pointer);

        return true;
    }

    /**
     * @param array<array-key,mixed> $value
     */
    private function handleMapSchemaKeywords(array $value, UsedComponents &$used, ?string $pointer): bool
    {
        foreach ($value as $schemaKey => $schemaValue) {
            if (is_array($schemaValue)) {
                $this->collectRefs($schemaValue, $used, $this->addSuffixToPointer($pointer, $schemaKey));
            }
        }

        return true;
    }

    /**
     * @param array<array-key,mixed> $docComponents
     */
    private function expandComponentsRefs(UsedComponents $initialRefs, array $docComponents): UsedComponents
    {
        $used = UsedComponents::new();
        $queue = UsageQueue::new($initialRefs);

        while ($queue->hasItems()) {
            foreach (self::COMPONENT_SECTIONS as $sectionName) {
                while (($name = $queue->pop($sectionName)) !== null) {
                    if (isset($used[$sectionName][$name])) {
                        continue;
                    }

                    $sectionDefs = $docComponents[$sectionName] ?? [];
                    if (is_array($sectionDefs) && ! isset($sectionDefs[$name])) {
                        continue; // Broken reference, so we skip it.
                    }

                    $used->mark($sectionName, $name);
                    $this->processComponentDefinition($sectionName, $name, $docComponents, $queue);
                }
            }
        }

        return $used;
    }

    /**
     * Process a single (section,name) pair pulled from the queue.
     *
     * @param array<array-key,mixed> $docComponents
     */
    private function processComponentDefinition(
        string $sectionName,
        string $name,
        array $docComponents,
        UsageQueue $queue,
    ): void {
        $sectionDefs = $docComponents[$sectionName] ?? [];
        Assert::isArray($sectionDefs, 'Component section must be an array');

        $def = $sectionDefs[$name] ?? null;
        if (! is_array($def)) {
            return;
        }

        $inner = UsedComponents::new();
        $basePointer = $this->addSuffixToPointer(sprintf('#/components/%s', $sectionName), $name);
        $this->collectRefs($def, $inner, $basePointer);

        foreach ($inner as $innerSection => $refs) {
            foreach ($refs as $refName => $_value) {
                $queue->add($innerSection, $refName);
            }
        }
    }

    private function resolveJsonPointer(string $pointer): mixed
    {
        Assert::startsWith($pointer, '#/', 'JSON Pointer must be a document-local reference starting with #/.');

        $segments = explode('/', substr($pointer, 2));
        $current = $this->rootDocument;

        foreach ($segments as $segment) {
            $seg = strtr($segment, ['~1' => '/', '~0' => '~']);
            if (is_array($current) && array_key_exists($seg, $current)) {
                $current = $current[$seg];
            } else {
                return null;
            }
        }

        return $current;
    }

    private function escapePointerSegment(string $segment): string
    {
        return strtr($segment, ['~' => '~0', '/' => '~1']);
    }

    private function addSuffixToPointer(?string $prefixPointer, mixed $key): ?string
    {
        if (is_int($key)) {
            $key = (string) $key;
        }

        return is_string($key) && $prefixPointer !== null
            ? sprintf('%s/%s', $prefixPointer, $this->escapePointerSegment($key))
            : null;
    }
}
