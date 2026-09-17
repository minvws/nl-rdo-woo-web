<?php

declare(strict_types=1);

namespace Admin\Form\Transformer;

use Shared\Domain\Exception\OrganisationPrefixArgumentException;
use Shared\ValueObject\OrganisationPrefix;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use function get_debug_type;
use function is_string;
use function sprintf;

final class StringToOrganisationPrefixTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (! $value instanceof OrganisationPrefix) {
            throw new TransformationFailedException(
                sprintf('Expected OrganisationPrefix, got %s', get_debug_type($value)),
            );
        }

        return $value->toString();
    }

    public function reverseTransform(mixed $value): OrganisationPrefix
    {
        if (! is_string($value)) {
            throw new TransformationFailedException(
                sprintf('Expected string, got %s', get_debug_type($value)),
            );
        }

        try {
            return OrganisationPrefix::create($value);
        } catch (OrganisationPrefixArgumentException $exception) {
            throw new TransformationFailedException(
                message: $exception->getMessage(),
                invalidMessage: $exception->getTranslationKey(),
                invalidMessageParameters: $exception->getParameters(),
            );
        }
    }
}
