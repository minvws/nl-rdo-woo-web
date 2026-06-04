<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Decorator;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model\MediaType;
use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\PathItem;
use ApiPlatform\OpenApi\Model\Paths;
use ApiPlatform\OpenApi\Model\RequestBody;
use ApiPlatform\OpenApi\OpenApi;
use ArrayObject;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Webmozart\Assert\Assert;

use function str_ends_with;

#[AsDecorator(decorates: 'api_platform.openapi.factory')]
final readonly class AddRequestBodyToUploadOperationsOpenApiFactoryDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
    ) {
    }

    /**
     * @param array<string,mixed> $context
     */
    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        return $openApi->withPaths($this->updateUploadRequestBodiesForPaths($openApi->getPaths()));
    }

    private function updateUploadRequestBodiesForPaths(Paths $paths): Paths
    {
        $newPaths = new Paths();

        foreach ($paths->getPaths() as $path => $pathItem) {
            Assert::isInstanceOf($pathItem, PathItem::class);

            $newPaths->addPath($path, $this->updateUploadOperationRequestBodyForPathItem($pathItem));
        }

        return $newPaths;
    }

    private function updateUploadOperationRequestBodyForPathItem(PathItem $pathItem): PathItem
    {
        $uploadOperation = $this->getUploadOperation($pathItem);
        if ($uploadOperation === null) {
            return $pathItem;
        }

        $newUploadOperation = $uploadOperation->withRequestBody(new RequestBody(
            description: 'The file to upload in raw binary format',
            content: new ArrayObject([
                'application/octet-stream' => new MediaType(
                    schema: new ArrayObject(['type' => 'string', 'format' => 'binary']),
                ),
            ]),
            required: true,
        ));

        return $pathItem->withPut($newUploadOperation);
    }

    private function getUploadOperation(PathItem $pathItem): ?Operation
    {
        $putOperation = $pathItem->getPut();
        if ($putOperation === null) {
            return null;
        }

        if ($putOperation->getOperationId() === null) {
            return null;
        }

        if (! $this->isUploadOperation($putOperation->getOperationId())) {
            return null;
        }

        return $putOperation;
    }

    private function isUploadOperation(string $operationId): bool
    {
        return str_ends_with($operationId, '_attachment_upload')
                || str_ends_with($operationId, '_main_document_upload')
                || $operationId === 'woo_decision_document_upload';
    }
}
