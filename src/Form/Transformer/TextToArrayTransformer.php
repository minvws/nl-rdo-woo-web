<?php

declare(strict_types=1);

namespace Shared\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Converts a separated string to an array and vice versa.
 *
 * @template-implements DataTransformerInterface<string, array|null>
 */
class TextToArrayTransformer implements DataTransformerInterface
{
    /**
     * @var non-empty-string
     */
    protected string $splitter;

    public function __construct(string $splitter)
    {
        $this->splitter = $splitter === '' ? ',' : $splitter;
    }

    /**
     * @return string[]|null
     */
    public function transform(mixed $value): ?array
    {
        if (! \is_string($value) || $value === '') {
            return null;
        }

        return explode($this->splitter, $value);
    }

    /**
     * @param string[]|null $value
     */
    public function reverseTransform(mixed $value): string
    {
        if (is_null($value)) {
            return '';
        }

        return join($this->splitter, $value);
    }
}
