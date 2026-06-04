<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Decorator;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\Response;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use PublicationApi\EventSubscriber\ApiVersionHeaderSubscriber;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Webmozart\Assert\Assert;

use function sprintf;
use function strtolower;
use function ucfirst;

#[AsDecorator(decorates: 'api_platform.openapi.factory', priority: 35)]
final readonly class ApiVersionHeaderOpenApiFactoryDecorator implements OpenApiFactoryInterface
{
    private const string COMPONENT_KEY = 'ApiVersion';

    public function __construct(
        private OpenApiFactoryInterface $decorated,
        #[Autowire(param: 'api_platform.version')]
        private string $apiVersion,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);
        $openApi = $this->addHeaderComponent($openApi);

        return $openApi->withPaths($this->addHeaderToAllResponses($openApi->getPaths()));
    }

    private function addHeaderComponent(OpenApi $openApi): OpenApi
    {
        $components = $openApi->getComponents();
        $headers = $components->getHeaders() ?? new ArrayObject();

        $headers[self::COMPONENT_KEY] = new ArrayObject([
            'description' => 'The API version',
            'schema' => new ArrayObject(['type' => 'string', 'example' => $this->apiVersion]),
        ]);

        return $openApi->withComponents($components->withHeaders($headers));
    }

    private function addHeaderToAllResponses(Paths $paths): Paths
    {
        $newPaths = new Paths();

        foreach ($paths->getPaths() as $path => $pathItem) {
            Assert::isInstanceOf($pathItem, PathItem::class);
            $newPaths->addPath($path, $this->addHeaderToPathItem($pathItem));
        }

        return $newPaths;
    }

    private function addHeaderToPathItem(PathItem $pathItem): PathItem
    {
        foreach (PathItem::$methods as $method) {
            Assert::string($method);

            $methodName = sprintf('get%s', ucfirst(strtolower($method)));
            $operation = $pathItem->{$methodName}();

            if (! $operation instanceof Operation) {
                continue;
            }

            $operation = $this->addHeaderToOperation($operation);

            $withMethodName = sprintf('with%s', ucfirst(strtolower($method)));
            $pathItem = $pathItem->{$withMethodName}($operation) ?? $pathItem;

            Assert::isInstanceOf($pathItem, PathItem::class);
        }

        return $pathItem;
    }

    private function addHeaderToOperation(Operation $operation): Operation
    {
        foreach ($operation->getResponses() ?? [] as $statusCode => $response) {
            if (! $response instanceof Response) {
                continue;
            }

            $headers = $response->getHeaders() ?? new ArrayObject();

            if (isset($headers[ApiVersionHeaderSubscriber::HEADER_NAME])) {
                continue;
            }

            $headers[ApiVersionHeaderSubscriber::HEADER_NAME] = new ArrayObject([
                '$ref' => sprintf('#/components/headers/%s', self::COMPONENT_KEY),
            ]);

            $operation = $operation->withResponse((string) $statusCode, $response->withHeaders($headers));
        }

        return $operation;
    }
}
