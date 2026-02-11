<?php

declare(strict_types=1);

namespace PublicationApi\Domain\OpenApi\Schema\Component;

use ApiPlatform\OpenApi\Model\Operation;
use ApiPlatform\OpenApi\Model\Response;
use Closure;

final readonly class OperationResponseDefinition
{
    /**
     * @param ?Closure(Operation $operation, string $path, string $httpMethod):bool $when
     */
    public function __construct(
        public string $statusCode,
        public Response $response,
        public ?Closure $when = null,
    ) {
    }
}
