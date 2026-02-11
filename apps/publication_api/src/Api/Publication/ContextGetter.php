<?php

declare(strict_types=1);

namespace PublicationApi\Api\Publication;

use ApiPlatform\Metadata\Operation;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;

trait ContextGetter
{
    /**
     * @param array<string,mixed> $context
     */
    private function getOperation(array $context): Operation
    {
        $operation = $context['operation'] ?? null;

        Assert::isInstanceOf($operation, Operation::class);

        return $operation;
    }

    /**
     * @param array<string,mixed> $context
     */
    private function getRequest(array $context): Request
    {
        $request = $context['request'] ?? null;

        Assert::isInstanceOf($request, Request::class);

        return $request;
    }
}
