<?php

declare(strict_types=1);

namespace Shared\Service;

use function array_key_exists;
use function is_array;
use function is_string;

class ApiPlatformService
{
    /**
     * @param array<array-key, mixed> $context
     */
    public static function getCursorFromContext(array $context): ?string
    {
        if (
            array_key_exists('filters', $context)
            && is_array($context['filters'])
            && array_key_exists('pagination', $context['filters'])
            && is_array($context['filters']['pagination'])
            && array_key_exists('cursor', $context['filters']['pagination'])
            && is_string($context['filters']['pagination']['cursor'])
        ) {
            return (string) $context['filters']['pagination']['cursor'];
        }

        return null;
    }
}
