<?php

declare(strict_types=1);

namespace Shared\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

use function is_null;

/**
 * Converts a null value to an empty string.
 *
 * @template-implements DataTransformerInterface<string, string|null>
 */
class NullToEmptyStringTransformer implements DataTransformerInterface
{
    public function transform($value): mixed
    {
        if (is_null($value)) {
            return '';
        }

        return $value;
    }

    public function reverseTransform($value): mixed
    {
        if (is_null($value)) {
            return '';
        }

        return $value;
    }
}
