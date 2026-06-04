<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Decorator;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Reference;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use PublicationApi\Domain\OpenApi\Schema\Component\OpenApiCommonResponsesProvider;
use PublicationApi\Domain\OpenApi\Schema\Component\OperationResponseDefinition;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Webmozart\Assert\Assert;

use function sprintf;
use function strtolower;
use function strtoupper;
use function ucfirst;

#[AsDecorator(decorates: 'api_platform.openapi.factory')]
final class CommonResponsesOpenApiFactoryDecorator implements OpenApiFactoryInterface
{
    /**
     * @var array<array-key,OperationResponseDefinition>
     */
    private array $commonResponses;

    /**
     * @param iterable<array-key,OpenApiCommonResponsesProvider> $commonResponsesProviders
     */
    public function __construct(
        private readonly OpenApiFactoryInterface $decorated,
        #[AutowireIterator('publication_api.open_api.common_responses_provider')]
        private readonly iterable $commonResponsesProviders,
    ) {
    }

    /**
     * @param array<string,mixed> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        return $openApi->withPaths($this->attachCommonResponsesToPaths($openApi->getPaths()));
    }

    private function attachCommonResponsesToPaths(Paths $paths): Paths
    {
        $newPaths = new Paths();

        foreach ($paths->getPaths() as $path => $pathItem) {
            Assert::isInstanceOf($pathItem, PathItem::class);

            $pathItem = $this->attachCommonResponsesToPathItem($path, $pathItem);

            $newPaths->addPath($path, $pathItem);
        }

        return $newPaths;
    }

    private function attachCommonResponsesToPathItem(string $path, PathItem $pathItem): PathItem
    {
        foreach (PathItem::$methods as $method) {
            Assert::string($method);

            $operation = $this->getOperation($pathItem, $method);
            if ($operation === null) {
                continue;
            }

            $operation = $this->addCommonResponsesToOperation($method, $operation, $path);

            $pathItem = $this->addOperationToPathItem($pathItem, $method, $operation);
        }

        return $pathItem;
    }

    private function addCommonResponsesToOperation(string $method, Operation $operation, string $path): Operation
    {
        foreach ($this->getCommonResponses() as $responseDefinition) {
            $operationResponses = $operation->getResponses() ?? [];

            $responseExists = ($operationResponses[$responseDefinition->statusCode] ?? null) !== null;
            if ($responseExists) {
                continue;
            }

            if ($responseDefinition->when !== null && ! ($responseDefinition->when)($operation, $path, strtoupper($method))) {
                continue;
            }

            $response = $responseDefinition->response instanceof Reference
                ? $this->unwrapReference($responseDefinition->response)
                : $responseDefinition->response;

            $operationResponses[$responseDefinition->statusCode] = $response;

            $operation = $operation->withResponses($operationResponses);
        }

        return $operation;
    }

    private function getOperation(PathItem $pathItem, string $method): ?Operation
    {
        $methodName = sprintf('get%s', ucfirst(strtolower($method)));

        $operation = $pathItem->{$methodName}();
        Assert::nullOrIsInstanceOf($operation, Operation::class);

        return $operation;
    }

    private function addOperationToPathItem(PathItem $pathItem, string $method, Operation $operation): PathItem
    {
        $methodName = sprintf('with%s', ucfirst(strtolower($method)));

        $newPathItem = $pathItem->{$methodName}($operation) ?? $pathItem;
        Assert::isInstanceOf($newPathItem, PathItem::class);

        return $newPathItem;
    }

    /**
     * @return array<array-key,OperationResponseDefinition>
     */
    private function getCommonResponses(): array
    {
        if (isset($this->commonResponses)) {
            return $this->commonResponses;
        }

        $responses = [];
        foreach ($this->commonResponsesProviders as $provider) {
            foreach ($provider->getCommonResponses() as $responseDefinition) {
                $responses[] = $responseDefinition;
            }
        }

        return $this->commonResponses = $responses;
    }

    private function unwrapReference(Reference $reference): ArrayObject
    {
        $ref = new ArrayObject(['$ref' => $reference->getRef()]);

        if ($reference->getSummary() !== null) {
            $ref['summary'] = $reference->getSummary();
        }

        if ($reference->getDescription() !== null) {
            $ref['description'] = $reference->getDescription();
        }

        foreach ($reference->getExtensionProperties() as $key => $value) {
            $ref[$key] = $value;
        }

        return $ref;
    }
}
