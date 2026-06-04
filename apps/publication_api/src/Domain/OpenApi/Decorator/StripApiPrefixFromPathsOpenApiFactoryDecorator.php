<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Decorator;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\OpenApi;
use PublicationApi\Api\Publication\PublicationV1Api;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Webmozart\Assert\Assert;

use function str_starts_with;
use function strlen;
use function substr;

#[AsDecorator(decorates: 'api_platform.openapi.factory')]
final readonly class StripApiPrefixFromPathsOpenApiFactoryDecorator implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        return $openApi->withPaths($this->stripPrefixFromPaths($openApi->getPaths()));
    }

    private function stripPrefixFromPaths(Paths $paths): Paths
    {
        $prefix = PublicationV1Api::API_PREFIX;
        $newPaths = new Paths();

        foreach ($paths->getPaths() as $path => $pathItem) {
            Assert::string($path);
            Assert::isInstanceOf($pathItem, PathItem::class);

            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
            }

            $newPaths->addPath($path, $pathItem);
        }

        return $newPaths;
    }
}
