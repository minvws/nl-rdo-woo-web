<?php

declare(strict_types=1);

namespace Shared\Form\Transformer;

use InvalidArgumentException;
use Shared\Domain\Publication\Subject\LandingPageSlug;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use function get_debug_type;
use function is_string;
use function sprintf;

class StringToLandingPageSlugTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (! $value instanceof LandingPageSlug) {
            throw new TransformationFailedException(
                sprintf('Expected LandingPageSlug, got %s', get_debug_type($value)),
            );
        }

        return $value->toString();
    }

    public function reverseTransform(mixed $value): LandingPageSlug
    {
        if ($value === '' || $value === null) {
            throw new TransformationFailedException(
                message: 'Landing page slug cannot be empty',
                invalidMessage: 'subject_landing_page_slug_required',
            );
        }

        if (! is_string($value)) {
            throw new TransformationFailedException(
                sprintf('Expected string, got %s', get_debug_type($value)),
            );
        }

        try {
            return LandingPageSlug::create($value);
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw new TransformationFailedException(
                message: $invalidArgumentException->getMessage(),
                invalidMessage: self::getTranslationKey($invalidArgumentException),
                invalidMessageParameters: [
                    '{{ min }}' => LandingPageSlug::MIN_LENGTH,
                    '{{ max }}' => LandingPageSlug::MAX_LENGTH,
                ],
            );
        }
    }

    private static function getTranslationKey(InvalidArgumentException $invalidArgumentException): string
    {
        return match ($invalidArgumentException->getCode()) {
            LandingPageSlug::ERROR_EMPTY => 'subject_landing_page_slug_required',
            LandingPageSlug::ERROR_INVALID_LENGTH => 'subject_landing_page_slug_invalid_length',
            default => 'subject_landing_page_slug_invalid_format',
        };
    }
}
