<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Decorator;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use PublicationApi\Domain\OpenApi\Schema\Component\OpenApiResponseComponentProvider;
use PublicationApi\Domain\OpenApi\Schema\Component\OperationResponseDefinition;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Webmozart\Assert\Assert;

use function sprintf;
use function strtolower;
use function strtoupper;
use function ucfirst;

#[AsDecorator(decorates: 'api_platform.openapi.factory', priority: 30)]
final class CommonResponsesOpenApiFactoryDecorator implements OpenApiFactoryInterface
{
    /**
     * @var array<array-key,OperationResponseDefinition>
     */
    private array $commonResponses;

    /**
     * @param iterable<array-key,OpenApiResponseComponentProvider> $responseProviders
     */
    public function __construct(
        private readonly OpenApiFactoryInterface $decorated,
        #[AutowireIterator('publication_api.open_api.response_component_provider')]
        private readonly iterable $responseProviders,
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
            $responseExists = (($operation->getResponses() ?? [])[$responseDefinition->statusCode] ?? null) !== null;
            if ($responseExists) {
                continue;
            }

            if ($responseDefinition->when !== null && ! ($responseDefinition->when)($operation, $path, strtoupper($method))) {
                continue;
            }

            $operation = $operation->withResponse(
                $responseDefinition->statusCode,
                $responseDefinition->response,
            );
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
        foreach ($this->responseProviders as $provider) {
            foreach ($provider->getResponses() as $responseDefinition) {
                $responses[] = $responseDefinition;
            }
        }

        return $this->commonResponses = $responses;
    }
}
