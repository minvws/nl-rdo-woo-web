<?php

declare(strict_types=1);

namespace Shared\Serializer;

use function array_key_exists;
use function is_string;

trait PathFromContext
{
    /**
     * @param array<array-key,mixed> $context
     */
    public function getPathFromContext(array $context): ?string
    {
        if (array_key_exists('deserialization_path', $context) && is_string($context['deserialization_path'])) {
            return $context['deserialization_path'];
        }

        return null;
    }
}
