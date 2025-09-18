<?php

declare(strict_types=1);

namespace App\Api\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Components;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Tag;
use ApiPlatform\OpenApi\OpenApi;
use App\Api\OpenApi\UsageDetector\OpenApiComponentsUsageDetector;
use App\Api\OpenApi\UsageDetector\UsedComponents;
use Webmozart\Assert\Assert;

final readonly class GroupedOpenApiFactory implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
        private OpenApiComponentsUsageDetector $openApiComponentsUsageDetector,
    ) {
    }

    /**
     * @param array<string,mixed> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $openApi = $this->filterPaths($openApi, $context);
        $openApi = $this->filterComponents($openApi, $this->openApiComponentsUsageDetector->detect($openApi));

        return $openApi;
    }

    /**
     * @param array<string,mixed> $context
     */
    private function filterPaths(OpenApi $openApi, array $context): OpenApi
    {
        $filterTag = $this->getFilterTagFromContext($context);
        if (! is_string($filterTag)) {
            return $openApi;
        }

        $originalPathsObject = $openApi->getPaths();
        $newPathsObject = new Paths();
        $newTags = [];

        /** @var array<string,PathItem> $originalPaths */
        $originalPaths = $originalPathsObject->getPaths();

        foreach ($originalPaths as $path => $pathItem) {
            foreach (PathItemIterator::from($pathItem) as $method => $operation) {
                $operationTags = $operation->getTags() ?? [];
                Assert::allString($operationTags, 'All tags must be strings.');

                if (in_array($filterTag, $operationTags, true)) {
                    $newTags = $this->addUniqueTags($newTags, $operationTags);
                } else {
                    $pathItem = $this->unsetOperation($pathItem, $method);
                }
            }

            if (iterator_count(PathItemIterator::from($pathItem))) {
                $newPathsObject->addPath($path, $pathItem);
            }
        }

        $newTags = array_map($this->mapToTag(...), array_keys($newTags));

        return $openApi
            ->withPaths($newPathsObject)
            ->withTags($newTags);
    }

    private function unsetOperation(PathItem $pathItem, string $method): PathItem
    {
        $methodName = sprintf('with%s', ucfirst(strtolower($method)));

        /** @var PathItem */
        return $pathItem->{$methodName}(null);
    }

    /**
     * @param array<string,true>      $currentTags
     * @param array<array-key,string> $tags
     *
     * @return array<string,true> $currentTags
     */
    private function addUniqueTags(array $currentTags, array $tags): array
    {
        foreach ($tags as $tag) {
            $currentTags[$tag] = true;
        }

        return $currentTags;
    }

    private function mapToTag(string $name): Tag
    {
        return new Tag(name: $name);
    }

    private function filterComponents(OpenApi $openApi, UsedComponents $used): OpenApi
    {
        $components = $openApi->getComponents();

        foreach (OpenApiComponentsUsageDetector::COMPONENT_SECTIONS as $section) {
            $sectionData = $this->getSection($section, $components);

            if ($sectionData === []) {
                continue;
            }

            $components = $this->setSection(
                $section,
                $components,
                array_intersect_key($sectionData, array_flip(array_keys($used[$section]))),
            );
        }

        return $components === $openApi->getComponents()
            ? $openApi
            : $openApi->withComponents($components);
    }

    /**
     * @param value-of<OpenApiComponentsUsageDetector::COMPONENT_SECTIONS> $section
     *
     * @return array<string,mixed>
     */
    private function getSection(string $section, Components $components): array
    {
        $methodName = sprintf('get%s', ucfirst($section));

        Assert::methodExists($components, $methodName, sprintf('Method %s does not exist in Components class.', $methodName));

        $sectionData = $components->{$methodName}();

        if (! $sectionData instanceof \ArrayObject) {
            return [];
        }

        /** @var array<string,mixed> */
        return $sectionData->getArrayCopy();
    }

    /**
     * @param value-of<OpenApiComponentsUsageDetector::COMPONENT_SECTIONS> $section
     * @param array<string,mixed>                                          $sectionData
     */
    private function setSection(string $section, Components $components, array $sectionData): Components
    {
        $methodName = sprintf('with%s', ucfirst($section));

        Assert::methodExists($components, $methodName, sprintf('Method %s does not exist in Components class.', $methodName));

        /** @var Components */
        return $components->{$methodName}(new \ArrayObject($sectionData));
    }

    /**
     * @param array<string,mixed> $context
     */
    private function getFilterTagFromContext(array $context = []): ?string
    {
        $filterTag = $context['filter_tag'] ?? null;
        if (! is_string($filterTag)) {
            return null;
        }

        return $filterTag;
    }
}
